<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApartmentListRequest;
use App\Models\Contact;
use App\Models\{Apartment, Page, Reservation, Review, Wishlist};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function contact(): View
    {
        return view('frontend.contact');
    }

    public function contactSubmit(Request $request)
    {
        $siteKey = (string) config('services.hcaptcha.site_key');
        $secret = (string) config('services.hcaptcha.secret');

        if ($siteKey === '' || $secret === '') {
            Log::warning('hCaptcha configuration is missing for contact form.');

            return back()
                ->withInput()
                ->withErrors(['hcaptcha' => __('frontpage.contact_page.validation.captcha_unavailable')]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'surname' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
            'h-captcha-response' => ['required', 'string'],
        ], [
            'required' => __('frontpage.contact_page.validation.required'),
            'email' => __('frontpage.contact_page.validation.email'),
            'max' => __('frontpage.contact_page.validation.max'),
            'h-captcha-response.required' => __('frontpage.contact_page.validation.captcha_required'),
        ], [
            'name' => __('frontpage.contact_page.form.name'),
            'surname' => __('frontpage.contact_page.form.surname'),
            'email' => __('frontpage.contact_page.form.email'),
            'message' => __('frontpage.contact_page.form.message'),
            'h-captcha-response' => __('frontpage.contact_page.form.captcha'),
        ]);

        [$captchaSuccess, $captchaErrors] = $this->verifyHcaptchaToken(
            $data['h-captcha-response'],
            (string) $request->ip()
        );

        if (! $captchaSuccess) {
            Log::info('hCaptcha verification failed on contact form.', [
                'ip' => $request->ip(),
                'errors' => $captchaErrors,
            ]);

            return back()
                ->withInput()
                ->withErrors(['hcaptcha' => __('frontpage.contact_page.validation.captcha_failed')]);
        }

        $contact = Contact::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'email' => $data['email'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $fullName = trim($data['name'] . ' ' . $data['surname']);
        $adminEmail = (string) config('website.contact_email');

        $mailBody = implode(PHP_EOL, [
            'New contact request from front website.',
            '',
            'Name: ' . $fullName,
            'Email: ' . $data['email'],
            'Contact ID: ' . $contact->id,
            'Message:',
            $data['message'],
            '',
            'Sent at: ' . now()->toDateTimeString(),
        ]);

        try {
            Mail::raw($mailBody, function ($message) use ($adminEmail, $fullName, $data) {
                $message->to($adminEmail)
                    ->replyTo($data['email'], $fullName)
                    ->subject('Aparto contact form');
            });
        } catch (\Throwable $exception) {
            Log::error('Contact form email send failed', [
                'email' => $data['email'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['contact' => __('frontpage.contact_page.send_error')]);
        }

        return redirect()
            ->route('contact.show')
            ->with('success', __('frontpage.contact_page.success'));
    }

    private function verifyHcaptchaToken(string $token, string $ip): array
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://api.hcaptcha.com/siteverify', [
                    'secret' => (string) config('services.hcaptcha.secret'),
                    'response' => $token,
                    'remoteip' => $ip,
                    'sitekey' => (string) config('services.hcaptcha.site_key'),
                ]);

            if (! $response->ok()) {
                return [false, ['request-failed']];
            }

            $json = $response->json() ?? [];

            if (! empty($json['success'])) {
                return [true, []];
            }

            return [false, $json['error-codes'] ?? []];
        } catch (\Throwable $exception) {
            Log::warning('hCaptcha verification request failed.', [
                'ip' => $ip,
                'error' => $exception->getMessage(),
            ]);

            return [false, ['request-failed']];
        }
    }

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

        $wishlistApartmentIds = $this->getWishlistApartmentIdsForCurrentUser();

        return view('frontend.index', compact('popularApartments', 'bestRatedApartments', 'newestApartments', 'cities', 'homepageStats', 'wishlistApartmentIds'));
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

        $wishlistApartmentIds = $this->getWishlistApartmentIdsForCurrentUser();

        if ($request->ajax()) {
            $html = view('frontend.partials.apartments-results', compact('apartments', 'wishlistApartmentIds'))->render();

            return response()->json(['html' => $html]);
        }

        return view('frontend.apartments', compact('apartments', 'cities', 'pageTitle', 'pageSubtitle', 'filterAction', 'resetUrl', 'wishlistApartmentIds'));
    }

    private function getWishlistApartmentIdsForCurrentUser(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return Wishlist::query()
            ->where('user_id', (int) auth()->id())
            ->pluck('apartment_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
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
