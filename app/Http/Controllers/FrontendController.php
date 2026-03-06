<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApartmentListRequest;
use App\Models\{Apartment, Page, Reservation, Review};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function index()
    {
        $sectionLimit = min(8, max(4, (int) config('website.homepage_section_apartments_limit', 8)));
        $periodDays = max(1, (int) config('website.homepage_trending_period_days', 30));
        $bestRatedMinReviews = min(5, max(3, (int) config('website.homepage_best_rated_min_reviews', 3)));
        $windowStart = now()->subDays($periodDays);

        $usedApartmentIds = collect();

        $popularApartmentIds = Reservation::query()
            ->selectRaw('apartment_id, COUNT(*) as reservations_count, MAX(created_at) as latest_reservation_at')
            ->where('status', '!=', 'canceled')
            ->where('created_at', '>=', $windowStart)
            ->whereHas('apartment', function (Builder $builder) {
                $builder->where('active', true);
            })
            ->groupBy('apartment_id')
            ->orderByDesc('reservations_count')
            ->orderByDesc('latest_reservation_at')
            ->limit($sectionLimit)
            ->pluck('apartment_id');

        $popularOrderMap = $popularApartmentIds->flip();

        $popularApartments = $this->homepageApartmentBaseQuery()
            ->whereIn('id', $popularApartmentIds)
            ->get()
            ->sortBy(function (Apartment $apartment) use ($popularOrderMap) {
                return $popularOrderMap->get($apartment->id, PHP_INT_MAX);
            })
            ->values();

        $usedApartmentIds = $usedApartmentIds
            ->merge($popularApartments->pluck('id'))
            ->values();

        $bestRatedApartmentIdsQuery = Review::query()
            ->selectRaw('apartment_id, AVG(rating) as average_rating, COUNT(*) as reviews_count, MAX(created_at) as latest_review_at')
            ->where('status', 'approved')
            ->where('created_at', '>=', $windowStart)
            ->whereHas('apartment', function (Builder $builder) {
                $builder->where('active', true);
            });

        if ($usedApartmentIds->isNotEmpty()) {
            $bestRatedApartmentIdsQuery->whereNotIn('apartment_id', $usedApartmentIds);
        }

        $bestRatedApartmentIds = $bestRatedApartmentIdsQuery
            ->groupBy('apartment_id')
            ->havingRaw('COUNT(*) >= ?', [$bestRatedMinReviews])
            ->orderByDesc('average_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('latest_review_at')
            ->limit($sectionLimit)
            ->pluck('apartment_id');

        $bestRatedOrderMap = $bestRatedApartmentIds->flip();

        $bestRatedApartments = $this->homepageApartmentBaseQuery()
            ->whereIn('id', $bestRatedApartmentIds)
            ->get()
            ->sortBy(function (Apartment $apartment) use ($bestRatedOrderMap) {
                return $bestRatedOrderMap->get($apartment->id, PHP_INT_MAX);
            })
            ->values();

        $usedApartmentIds = $usedApartmentIds
            ->merge($bestRatedApartments->pluck('id'))
            ->unique()
            ->values();

        $newestApartmentsQuery = $this->homepageApartmentBaseQuery();

        if ($usedApartmentIds->isNotEmpty()) {
            $newestApartmentsQuery->whereNotIn('id', $usedApartmentIds);
        }

        $newestApartments = $newestApartmentsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($sectionLimit)
            ->get();

        $homepageStats = Apartment::query()
            ->where('active', true)
            ->selectRaw('COUNT(*) as available_count')
            ->selectRaw('MIN(price_per_night) as min_price')
            ->selectRaw('SUM(CASE WHEN parking = 1 THEN 1 ELSE 0 END) as parking_count')
            ->first();

        $cities = Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('frontend.index', compact('popularApartments', 'bestRatedApartments', 'newestApartments', 'cities', 'homepageStats'));
    }

    private function homepageApartmentBaseQuery(): Builder
    {
        return Apartment::query()
            ->where('active', true)
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating');
    }

    public function show($id)
    {
        $apartment = Apartment::withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->findOrFail($id);

        $reservationRanges = $apartment->reservations()
            ->where('status', 'confirmed')
            ->whereNull('deleted_at')
            ->get(['date_from', 'date_to'])
            ->map(function ($reservation) {
                return [
                    'from' => Carbon::parse($reservation->date_from)->toDateString(),
                    'to' => Carbon::parse($reservation->date_to)->toDateString(),
                ];
            })
            ->values();

        // Add blocked dates to reservation ranges.
        $blockedDates = $apartment->blockedPeriods()
            ->get(['date_from', 'date_to'])
            ->map(function ($blocked) {
                return [
                    'from' => Carbon::parse($blocked->date_from)->toDateString(),
                    'to' => Carbon::parse($blocked->date_to)->toDateString(),
                ];
            })
            ->values();

        // Legacy fallback if normalized rows are not present yet.
        if ($blockedDates->isEmpty() && !empty($apartment->blocked_dates)) {
            $blockedDates = collect($apartment->blocked_dates)
                ->map(function ($blocked) {
                    return [
                        'from' => isset($blocked['from']) ? Carbon::parse($blocked['from'])->toDateString() : null,
                        'to' => isset($blocked['to']) ? Carbon::parse($blocked['to'])->toDateString() : null,
                    ];
                })
                ->filter(function ($blocked) {
                    return $blocked['from'] && $blocked['to'];
                })
                ->values();
        }

        $reservationRanges = $reservationRanges->concat($blockedDates)->values();

        // Prepare custom pricing data
        $customPricing = collect($apartment->custom_pricing ?? [])
            ->map(function ($pricing) {
                return [
                    'from' => isset($pricing['from']) ? Carbon::parse($pricing['from'])->toDateString() : null,
                    'to' => isset($pricing['to']) ? Carbon::parse($pricing['to'])->toDateString() : null,
                    'price' => $pricing['price'] ?? null,
                ];
            })
            ->filter(function ($pricing) {
                return $pricing['from'] && $pricing['to'] && $pricing['price'];
            })
            ->values();

        // Fetch approved reviews with user data
        $reviews = $apartment->approvedReviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if authenticated user can review
        $userCanReview = false;
        $userHasReviewed = false;

        if (auth()->check()) {
            // Check if user has a past reservation for this apartment.
            // Support both new (with user_id) and old (without user_id) reservations.
            // For development/testing: Accept 'pending' or 'confirmed' status.
            $hasPastReservation = $apartment->reservations()
                ->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->orWhere('email', auth()->user()->email);
                })
                ->where('date_to', '<', now())
                ->whereIn('status', ['confirmed', 'pending'])
                ->exists();

            // Check if user already reviewed this apartment.
            $userHasReviewed = $apartment->reviews()
                ->where('user_id', auth()->id())
                ->exists();

            $userCanReview = $hasPastReservation && !$userHasReviewed;
        }

        return view('frontend.show', compact('apartment', 'reservationRanges', 'customPricing', 'reviews', 'userCanReview', 'userHasReviewed'));
    }

    public function list(ApartmentListRequest $request)
    {
        $query = $this->homepageApartmentBaseQuery()
            ->orderByDesc('id');

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments.title'),
            pageSubtitle: __('frontpage.apartments.subtitle'),
            filterAction: route('apartments.index'),
            resetUrl: route('apartments.index'),
        );
    }

    public function popular(ApartmentListRequest $request)
    {
        $popularCountsSubquery = Reservation::query()
            ->selectRaw('apartment_id, COUNT(*) as reservations_count, MAX(created_at) as latest_reservation_at')
            ->where('status', '!=', 'canceled')
            ->groupBy('apartment_id');

        $query = $this->homepageApartmentBaseQuery()
            ->joinSub($popularCountsSubquery, 'popular_counts', function ($join) {
                $join->on('apartments.id', '=', 'popular_counts.apartment_id');
            })
            ->select('apartments.*')
            ->orderByDesc('popular_counts.reservations_count')
            ->orderByDesc('popular_counts.latest_reservation_at')
            ->orderByDesc('apartments.id');

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments_popular.title'),
            pageSubtitle: __('frontpage.apartments_popular.subtitle'),
            filterAction: route('apartments.popular'),
            resetUrl: route('apartments.popular'),
        );
    }

    public function reviewed(ApartmentListRequest $request)
    {
        $reviewedScoresSubquery = Review::query()
            ->selectRaw('apartment_id, AVG(rating) as average_rating_all, COUNT(*) as reviews_count_all, MAX(created_at) as latest_review_at')
            ->where('status', 'approved')
            ->groupBy('apartment_id');

        $query = $this->homepageApartmentBaseQuery()
            ->joinSub($reviewedScoresSubquery, 'reviewed_scores', function ($join) {
                $join->on('apartments.id', '=', 'reviewed_scores.apartment_id');
            })
            ->select('apartments.*')
            ->orderByDesc('reviewed_scores.average_rating_all')
            ->orderByDesc('reviewed_scores.reviews_count_all')
            ->orderByDesc('reviewed_scores.latest_review_at')
            ->orderByDesc('apartments.id');

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments_reviewed.title'),
            pageSubtitle: __('frontpage.apartments_reviewed.subtitle'),
            filterAction: route('apartments.reviewed'),
            resetUrl: route('apartments.reviewed'),
        );
    }

    private function renderApartmentList(
        ApartmentListRequest $request,
        Builder $query,
        string $pageTitle,
        string $pageSubtitle,
        string $filterAction,
        string $resetUrl,
    ): View|JsonResponse {
        $filters = $request->validated();

        $this->applyApartmentListFilters($query, $filters);

        $apartments = $query
            ->paginate((int) config('website.apartments_per_page', 12))
            ->appends($filters);

        $cities = Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        if ($request->ajax()) {
            $html = view('frontend.partials.apartments-results', compact('apartments'))->render();

            return response()->json(['html' => $html]);
        }

        return view('frontend.apartments', compact('apartments', 'cities', 'pageTitle', 'pageSubtitle', 'filterAction', 'resetUrl'));
    }

    private function applyApartmentListFilters(Builder $query, array $filters): void
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
            // Reservations use check-out as exclusive end date.
            $query->whereDoesntHave('reservations', function ($builder) use ($dateFrom, $dateTo) {
                $builder->where('status', '!=', 'canceled')
                    ->where('date_from', '<', $dateTo)
                    ->where('date_to', '>', $dateFrom);
            });

            // Blocked periods are inclusive date ranges in admin UI.
            $query->whereDoesntHave('blockedPeriods', function ($builder) use ($dateFrom, $dateTo) {
                $builder->where('date_from', '<', $dateTo)
                    ->where('date_to', '>=', $dateFrom);
            });
        }
    }

    public function page(string $slug)
    {
        $page = Page::where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontend.page', compact('page'));
    }
}
