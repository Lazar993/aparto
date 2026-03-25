<?php

namespace App\Http\Repository;

use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApartmentRepository
{
    public function baseActiveQuery(): Builder
    {
        return Apartment::query()
            ->where('active', true)
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating');
    }

    public function getPopularApartmentIds(int $limit, Carbon $windowStart): Collection
    {
        return Reservation::query()
            ->selectRaw('apartment_id, COUNT(*) as reservations_count, MAX(created_at) as latest_reservation_at')
            ->where('status', '!=', 'canceled')
            ->where('created_at', '>=', $windowStart)
            ->whereHas('apartment', fn (Builder $b) => $b->where('active', true))
            ->groupBy('apartment_id')
            ->orderByDesc('reservations_count')
            ->orderByDesc('latest_reservation_at')
            ->limit($limit)
            ->pluck('apartment_id');
    }

    public function getApartmentsByIdsOrdered(Collection $ids, Collection $orderMap): Collection
    {
        return $this->baseActiveQuery()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Apartment $apartment) => $orderMap->get($apartment->id, PHP_INT_MAX))
            ->values();
    }

    public function getBestRatedApartmentIds(
        int $limit,
        Carbon $windowStart,
        int $minReviews,
        Collection $excludeIds,
    ): Collection {
        $query = Review::query()
            ->selectRaw('apartment_id, AVG(rating) as average_rating, COUNT(*) as reviews_count, MAX(created_at) as latest_review_at')
            ->where('status', 'approved')
            ->where('created_at', '>=', $windowStart)
            ->whereHas('apartment', fn (Builder $b) => $b->where('active', true));

        if ($excludeIds->isNotEmpty()) {
            $query->whereNotIn('apartment_id', $excludeIds);
        }

        return $query
            ->groupBy('apartment_id')
            ->havingRaw('COUNT(*) >= ?', [$minReviews])
            ->orderByDesc('average_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('latest_review_at')
            ->limit($limit)
            ->pluck('apartment_id');
    }

    public function getNewestApartments(int $limit, Collection $excludeIds): Collection
    {
        $query = $this->baseActiveQuery();

        if ($excludeIds->isNotEmpty()) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function getHomepageStats(): object
    {
        return Apartment::query()
            ->where('active', true)
            ->selectRaw('COUNT(*) as available_count')
            ->selectRaw('MIN(price_per_night) as min_price')
            ->selectRaw('SUM(CASE WHEN parking = 1 THEN 1 ELSE 0 END) as parking_count')
            ->first();
    }

    public function getActiveCities(): Collection
    {
        return Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function findWithReviewStats(int $id): Apartment
    {
        return Apartment::withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->findOrFail($id);
    }

    public function getReservationRanges(Apartment $apartment): Collection
    {
        return $apartment->reservations()
            ->where('status', '!=', 'canceled')
            ->whereNull('deleted_at')
            ->get(['date_from', 'date_to'])
            ->map(fn ($reservation) => [
                'from' => Carbon::parse($reservation->date_from)->toDateString(),
                'to' => Carbon::parse($reservation->date_to)->toDateString(),
            ])
            ->values();
    }

    public function getBlockedDateRanges(Apartment $apartment): Collection
    {
        $blockedDates = $apartment->blockedPeriods()
            ->get(['date_from', 'date_to'])
            ->map(fn ($blocked) => [
                'from' => Carbon::parse($blocked->date_from)->toDateString(),
                'to' => Carbon::parse($blocked->date_to)->addDay()->toDateString(),
            ])
            ->values();

        // Legacy fallback if normalized rows are not present yet.
        if ($blockedDates->isEmpty() && ! empty($apartment->blocked_dates)) {
            $blockedDates = collect($apartment->blocked_dates)
                ->map(function ($blocked) {
                    $from = isset($blocked['from']) ? Carbon::parse($blocked['from'])->toDateString() : null;
                    $toInclusive = isset($blocked['to']) ? Carbon::parse($blocked['to']) : null;

                    return [
                        'from' => $from,
                        'to' => $toInclusive ? $toInclusive->addDay()->toDateString() : null,
                    ];
                })
                ->filter(fn ($blocked) => $blocked['from'] && $blocked['to'])
                ->values();
        }

        return $blockedDates;
    }

    public function getCustomPricing(Apartment $apartment): Collection
    {
        return collect($apartment->custom_pricing ?? [])
            ->map(fn ($pricing) => [
                'from' => isset($pricing['from']) ? Carbon::parse($pricing['from'])->toDateString() : null,
                'to' => isset($pricing['to']) ? Carbon::parse($pricing['to'])->toDateString() : null,
                'price' => $pricing['price'] ?? null,
            ])
            ->filter(fn ($pricing) => $pricing['from'] && $pricing['to'] && $pricing['price'])
            ->values();
    }

    public function getHostApartments(int $hostId): Collection
    {
        return Apartment::where('user_id', $hostId)
            ->where('active', true)
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->orderByDesc('id')
            ->get();
    }

    public function getHostApartmentsCount(int $hostId): int
    {
        return Apartment::where('user_id', $hostId)
            ->where('active', true)
            ->count();
    }

    public function applyListFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $city = trim((string) ($filters['city'] ?? ''));
        if ($city !== '') {
            $query->where('city', 'like', "%{$city}%");
        }

        $minPrice = $filters['min_price'] ?? null;
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price_per_night', '>=', (float) $minPrice);
        }

        $maxPrice = $filters['max_price'] ?? null;
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price_per_night', '<=', (float) $maxPrice);
        }

        $guests = $filters['guests'] ?? null;
        if ($guests !== null && $guests !== '') {
            $query->where('guest_number', '>=', (int) $guests);
        }

        $parking = $filters['parking'] ?? null;
        if ($parking !== null && $parking !== '') {
            $query->where('parking', (bool) ((int) $parking));
        }

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if ($dateFrom && $dateTo) {
            $query->whereDoesntHave('reservations', function ($builder) use ($dateFrom, $dateTo) {
                $builder->where('status', '!=', 'canceled')
                    ->where('date_from', '<', $dateTo)
                    ->where('date_to', '>', $dateFrom);
            });

            $query->whereDoesntHave('blockedPeriods', function ($builder) use ($dateFrom, $dateTo) {
                $builder->where('date_from', '<', $dateTo)
                    ->where('date_to', '>=', $dateFrom);
            });
        }
    }

    public function getPopularListingQuery(): Builder
    {
        $popularCountsSubquery = Reservation::query()
            ->selectRaw('apartment_id, COUNT(*) as reservations_count, MAX(created_at) as latest_reservation_at')
            ->where('status', '!=', 'canceled')
            ->groupBy('apartment_id');

        return $this->baseActiveQuery()
            ->joinSub($popularCountsSubquery, 'popular_counts', function ($join) {
                $join->on('apartments.id', '=', 'popular_counts.apartment_id');
            })
            ->select('apartments.*')
            ->orderByDesc('popular_counts.reservations_count')
            ->orderByDesc('popular_counts.latest_reservation_at')
            ->orderByDesc('apartments.id');
    }

    public function getReviewedListingQuery(): Builder
    {
        $reviewedScoresSubquery = Review::query()
            ->selectRaw('apartment_id, AVG(rating) as average_rating_all, COUNT(*) as reviews_count_all, MAX(created_at) as latest_review_at')
            ->where('status', 'approved')
            ->groupBy('apartment_id');

        return $this->baseActiveQuery()
            ->joinSub($reviewedScoresSubquery, 'reviewed_scores', function ($join) {
                $join->on('apartments.id', '=', 'reviewed_scores.apartment_id');
            })
            ->select('apartments.*')
            ->orderByDesc('reviewed_scores.average_rating_all')
            ->orderByDesc('reviewed_scores.reviews_count_all')
            ->orderByDesc('reviewed_scores.latest_review_at')
            ->orderByDesc('apartments.id');
    }
}
