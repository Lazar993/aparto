<?php

namespace App\Http\Controllers;

use App\Models\{Apartment, Reservation, Review, Page};

use Carbon\Carbon;

class FrontendController extends Controller
{
    public function index()
    {
        // Fetch only active apartments for the homepage
        $apartments = Apartment::where('active', true)->get();

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
        $apartment = Apartment::findOrFail($id);

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

        // Add blocked dates to reservation ranges
        $blockedDates = collect($apartment->blocked_dates ?? [])->map(function ($blocked) {
            return [
                'from' => isset($blocked['from']) ? Carbon::parse($blocked['from'])->toDateString() : null,
                'to' => isset($blocked['to']) ? Carbon::parse($blocked['to'])->toDateString() : null,
            ];
        })->filter(function ($blocked) {
            return $blocked['from'] && $blocked['to'];
        })->values();

        $reservationRanges = $reservationRanges->concat($blockedDates)->values();

        // Prepare custom pricing data
        $customPricing = collect($apartment->custom_pricing ?? [])->map(function ($pricing) {
            return [
                'from' => isset($pricing['from']) ? Carbon::parse($pricing['from'])->toDateString() : null,
                'to' => isset($pricing['to']) ? Carbon::parse($pricing['to'])->toDateString() : null,
                'price' => $pricing['price'] ?? null,
            ];
        })->filter(function ($pricing) {
            return $pricing['from'] && $pricing['to'] && $pricing['price'];
        })->values();

        // Fetch approved reviews with user data
        $reviews = $apartment->approvedReviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if authenticated user can review
        $userCanReview = false;
        $userHasReviewed = false;

        if (auth()->check()) {
            // Check if user has a past reservation for this apartment
            // Support both new (with user_id) and old (without user_id) reservations
            // For development/testing: Accept 'pending' or 'confirmed' status
            $hasPastReservation = $apartment->reservations()
                ->where(function($query) {
                    $query->where('user_id', auth()->id())
                          ->orWhere('email', auth()->user()->email);
                })
                ->where('date_to', '<', now())
                ->whereIn('status', ['confirmed', 'pending']) // Allow pending for testing
                ->exists();

            // Check if user already reviewed this apartment
            $userHasReviewed = $apartment->reviews()
                ->where('user_id', auth()->id())
                ->exists();

            $userCanReview = $hasPastReservation && !$userHasReviewed;
        }

        return view('frontend.show', compact('apartment', 'reservationRanges', 'customPricing', 'reviews', 'userCanReview', 'userHasReviewed'));
    }

    public function list(\Illuminate\Http\Request $request)
    {
        $query = Apartment::where('active', true);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $city = trim((string) $request->query('city', ''));
        if ($city !== '') {
            $query->where('city', $city);
        }

        $minPrice = $request->query('min_price');
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price_per_night', '>=', (float) $minPrice);
        }

        $maxPrice = $request->query('max_price');
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price_per_night', '<=', (float) $maxPrice);
        }

        $parking = $request->query('parking');
        if ($parking !== null && $parking !== '') {
            $query->where('parking', (bool) ((int) $parking));
        }

        $apartments = $query->orderByDesc('id')
            ->paginate(9)
            ->appends($request->query());

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
