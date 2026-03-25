<?php

namespace App\Http\Controllers;

use App\Http\Repository\ApartmentRepository;
use App\Http\Repository\ReservationRepository;
use App\Http\Repository\ReviewRepository;
use App\Http\Repository\WishlistRepository;
use App\Http\Requests\ApartmentListRequest;
use App\Models\{Apartment, Page, User};
use App\Services\ContactService;
use App\Services\HCaptchaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(
        private ApartmentRepository $apartmentRepository,
        private ReviewRepository $reviewRepository,
        private ReservationRepository $reservationRepository,
        private WishlistRepository $wishlistRepository,
        private HCaptchaService $hCaptchaService,
        private ContactService $contactService,
    ) {}

    public function contact(): View
    {
        return view('frontend.contact');
    }

    public function contactSubmit(Request $request)
    {
        if (! $this->hCaptchaService->isConfigured()) {
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

        [$captchaSuccess, $captchaErrors] = $this->hCaptchaService->verify(
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

        $contact = $this->contactService->createContact($data, (string) $request->ip(), (string) $request->userAgent());

        try {
            $this->contactService->sendAdminNotificationEmail($contact, $data);
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['contact' => __('frontpage.contact_page.send_error')]);
        }

        return redirect()
            ->route('contact.show')
            ->with('success', __('frontpage.contact_page.success'));
    }

    public function index()
    {
        $sectionLimit = min(8, max(4, (int) config('website.homepage_section_apartments_limit', 8)));
        $periodDays = max(1, (int) config('website.homepage_trending_period_days', 30));
        $bestRatedMinReviews = min(5, max(3, (int) config('website.homepage_best_rated_min_reviews', 3)));
        $windowStart = now()->subDays($periodDays);

        $usedApartmentIds = collect();

        $popularApartmentIds = $this->apartmentRepository->getPopularApartmentIds($sectionLimit, $windowStart);
        $popularApartments = $this->apartmentRepository->getApartmentsByIdsOrdered($popularApartmentIds, $popularApartmentIds->flip());

        $usedApartmentIds = $usedApartmentIds->merge($popularApartments->pluck('id'))->values();

        $bestRatedApartmentIds = $this->apartmentRepository->getBestRatedApartmentIds(
            $sectionLimit, $windowStart, $bestRatedMinReviews, $usedApartmentIds
        );
        $bestRatedApartments = $this->apartmentRepository->getApartmentsByIdsOrdered($bestRatedApartmentIds, $bestRatedApartmentIds->flip());

        $usedApartmentIds = $usedApartmentIds->merge($bestRatedApartments->pluck('id'))->unique()->values();

        $newestApartments = $this->apartmentRepository->getNewestApartments($sectionLimit, $usedApartmentIds);
        $homepageStats = $this->apartmentRepository->getHomepageStats();
        $cities = $this->apartmentRepository->getActiveCities();
        $wishlistApartmentIds = $this->getWishlistApartmentIdsForCurrentUser();

        return view('frontend.index', compact('popularApartments', 'bestRatedApartments', 'newestApartments', 'cities', 'homepageStats', 'wishlistApartmentIds'));
    }

    public function show(string $locale, $id, $slug = null)
    {
        $apartment = $this->apartmentRepository->findWithReviewStats($id);

        if ($slug !== $apartment->slug) {
            return redirect()->route('apartments.show', [
                'locale' => $locale,
                'id' => $apartment->id,
                'slug' => $apartment->slug,
            ], 301);
        }

        $reservationRanges = $this->apartmentRepository->getReservationRanges($apartment);
        $blockedDates = $this->apartmentRepository->getBlockedDateRanges($apartment);
        $reservationRanges = $reservationRanges->concat($blockedDates)->values();

        $customPricing = $this->apartmentRepository->getCustomPricing($apartment);
        $reviews = $this->reviewRepository->getApprovedReviews($apartment);

        $userCanReview = false;
        $userHasReviewed = false;

        if (auth()->check()) {
            $hasPastReservation = $this->reservationRepository->userHasPastReservation(
                $apartment->id, auth()->id(), auth()->user()->email
            );
            $userHasReviewed = $this->reviewRepository->userHasReviewed(auth()->id(), $apartment->id);
            $userCanReview = $hasPastReservation && ! $userHasReviewed;
        }

        $reviewLoginUrl = route('apartments.review.entry', ['apartment' => $apartment->getKey()]);

        $host = $apartment->user;
        $hostTotalReviews = 0;
        $hostAverageRating = null;
        $hostApartmentsCount = 0;

        if ($host) {
            $hostReviewStats = $this->reviewRepository->getHostReviewStats($host->id);
            $hostTotalReviews = (int) $hostReviewStats->total;
            $hostAverageRating = $hostTotalReviews > 0
                ? round($hostReviewStats->rating_sum / $hostTotalReviews, 1)
                : null;
            $hostApartmentsCount = $this->apartmentRepository->getHostApartmentsCount($host->id);
        }

        return view('frontend.show', compact(
            'apartment', 'reservationRanges', 'customPricing', 'reviews',
            'userCanReview', 'userHasReviewed', 'reviewLoginUrl',
            'host', 'hostTotalReviews', 'hostAverageRating', 'hostApartmentsCount'
        ));
    }

    public function hostProfile(string $locale, $id, $slug = null)
    {
        $host = User::where('user_type', User::TYPE_HOST)->findOrFail($id);

        if ($slug !== $host->slug) {
            return redirect()->route('host.profile', [
                'locale' => $locale,
                'id' => $host->id,
                'slug' => $host->slug,
            ], 301);
        }

        $apartments = $this->apartmentRepository->getHostApartments($host->id);

        $hostReviewStats = $this->reviewRepository->getHostReviewStats($host->id);
        $hostTotalReviews = (int) $hostReviewStats->total;
        $hostAverageRating = $hostTotalReviews > 0
            ? round($hostReviewStats->rating_sum / $hostTotalReviews, 1)
            : null;

        return view('frontend.host_profile', compact(
            'host', 'apartments', 'hostTotalReviews', 'hostAverageRating'
        ));
    }

    public function reviewEntry(Apartment $apartment): RedirectResponse
    {
        return redirect()->route('apartments.show', [
            'id' => $apartment->getKey(),
            'slug' => $apartment->slug,
            'review' => 'write',
        ]);
    }

    public function list(ApartmentListRequest $request)
    {
        $query = $this->apartmentRepository->baseActiveQuery()
            ->orderByDesc('id');

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments.title'),
            pageSubtitle: __('frontpage.apartments.subtitle'),
            filterAction: route('apartments.index'),
            resetUrl: route('apartments.index'),
            seoTitle: __('frontpage.seo.apartments_index.title'),
            seoDescription: __('frontpage.seo.apartments_index.description'),
            seoKeywords: __('frontpage.seo.apartments_index.keywords'),
        );
    }

    public function popular(ApartmentListRequest $request)
    {
        $query = $this->apartmentRepository->getPopularListingQuery();

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments_popular.title'),
            pageSubtitle: __('frontpage.apartments_popular.subtitle'),
            filterAction: route('apartments.popular'),
            resetUrl: route('apartments.popular'),
            seoTitle: __('frontpage.seo.apartments_popular.title'),
            seoDescription: __('frontpage.seo.apartments_popular.description'),
            seoKeywords: __('frontpage.seo.apartments_popular.keywords'),
        );
    }

    public function reviewed(ApartmentListRequest $request)
    {
        $query = $this->apartmentRepository->getReviewedListingQuery();

        return $this->renderApartmentList(
            request: $request,
            query: $query,
            pageTitle: __('frontpage.apartments_reviewed.title'),
            pageSubtitle: __('frontpage.apartments_reviewed.subtitle'),
            filterAction: route('apartments.reviewed'),
            resetUrl: route('apartments.reviewed'),
            seoTitle: __('frontpage.seo.apartments_reviewed.title'),
            seoDescription: __('frontpage.seo.apartments_reviewed.description'),
            seoKeywords: __('frontpage.seo.apartments_reviewed.keywords'),
        );
    }

    private function renderApartmentList(
        ApartmentListRequest $request,
        Builder $query,
        string $pageTitle,
        string $pageSubtitle,
        string $filterAction,
        string $resetUrl,
        string $seoTitle = '',
        string $seoDescription = '',
        string $seoKeywords = '',
    ): View|JsonResponse {
        $filters = $request->validated();

        $this->apartmentRepository->applyListFilters($query, $filters);

        $apartments = $query
            ->paginate((int) config('website.apartments_per_page', 12))
            ->appends($filters);

        $cities = $this->apartmentRepository->getActiveCities();
        $wishlistApartmentIds = $this->getWishlistApartmentIdsForCurrentUser();

        if ($request->ajax()) {
            $html = view('frontend.partials.apartments-results', compact('apartments', 'wishlistApartmentIds'))->render();

            return response()->json(['html' => $html]);
        }

        return view('frontend.apartments', compact('apartments', 'cities', 'pageTitle', 'pageSubtitle', 'filterAction', 'resetUrl', 'wishlistApartmentIds', 'seoTitle', 'seoDescription', 'seoKeywords'));
    }

    private function getWishlistApartmentIdsForCurrentUser(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return $this->wishlistRepository->getUserWishlistIds((int) auth()->id());
    }

    public function page(Request $request, string $locale, string $slug): View
    {
        $allowedLocales = ['sr', 'en', 'ru'];

        $page = Page::where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('frontend.page', [
            'page' => $page,
            'pageSlug' => $slug,
            'availableLocales' => $allowedLocales,
        ]);
    }
}
