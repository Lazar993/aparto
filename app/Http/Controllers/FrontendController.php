<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApartmentListRequest;
use App\Models\{Apartment, Page};
use Carbon\Carbon;

class FrontendController extends Controller
{
    public function index()
    {
        // Fetch only active apartments for the homepage
        $apartments = Apartment::where('active', true)
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->get();

        $cities = Apartment::where('active', true)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('frontend.index', compact('apartments', 'cities'));
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
        $filters = $request->validated();

        $query = Apartment::where('active', true)
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating');

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

        $apartments = $query->orderByDesc('id')
            ->paginate(9)
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

        return view('frontend.apartments', compact('apartments', 'cities'));
    }

    public function page(string $slug)
    {
        $page = Page::where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontend.page', compact('page'));
    }
}
