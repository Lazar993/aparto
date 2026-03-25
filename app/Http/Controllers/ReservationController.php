<?php

namespace App\Http\Controllers;

use App\Http\Repository\ReservationRepository;
use App\Http\Repository\WishlistRepository;
use App\Models\Apartment;
use App\Services\HCaptchaService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private WishlistRepository $wishlistRepository,
        private ReservationService $reservationService,
        private HCaptchaService $hCaptchaService,
    ) {}

    public function myReservations(Request $request)
    {
        $user = $request->user();
        $perPage = (int) config('website.apartments_per_page');

        $reservations = $this->reservationRepository->getUserReservations($user, $perPage);

        $wishlistApartmentIds = $this->wishlistRepository->getUserWishlistIds((int) $user->id);
        $wishlistApartments = $this->wishlistRepository->getWishlistApartments((int) $user->id, $perPage);

        return view('frontend.my-profile', compact('reservations', 'wishlistApartments', 'wishlistApartmentIds'));
    }

    public function store(Request $request, Apartment $apartment)
    {
        if (! $this->hCaptchaService->isConfigured()) {
            Log::warning('hCaptcha configuration is missing for reservation form.');

            return back()
                ->withInput($request->except('h-captcha-response'))
                ->withErrors(['hcaptcha' => __('frontpage.reservation.validation.captcha_unavailable')]);
        }

        $rules = [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after:date_from'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'regex:/^\d{9,}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
            'h-captcha-response' => ['required', 'string'],
        ];

        $messages = [
            'required' => __('frontpage.reservation.validation.required'),
            'date' => __('frontpage.reservation.validation.date'),
            'date_to.after' => __('frontpage.reservation.validation.after'),
            'email' => __('frontpage.reservation.validation.email'),
            'phone.regex' => __('frontpage.reservation.validation.phone'),
            'h-captcha-response.required' => __('frontpage.reservation.validation.captcha_required'),
        ];

        $attributes = trans('frontpage.reservation.attributes');

        $data = $request->validate($rules, $messages, $attributes);

        [$captchaSuccess, $captchaErrors] = $this->hCaptchaService->verify(
            $data['h-captcha-response'],
            (string) $request->ip()
        );

        if (! $captchaSuccess) {
            Log::info('hCaptcha verification failed on reservation form.', [
                'ip' => $request->ip(),
                'errors' => $captchaErrors,
            ]);

            return back()
                ->withInput($request->except('h-captcha-response'))
                ->withErrors(['hcaptcha' => __('frontpage.reservation.validation.captcha_failed')]);
        }

        try {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $data['date_from'], 'UTC')->startOfDay();
            $dateTo = Carbon::createFromFormat('Y-m-d', $data['date_to'], 'UTC')->startOfDay();
        } catch (\Exception $e) {
            Log::error('Date parsing error', [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors([
                'date_from' => __('frontpage.reservation.validation.date'),
            ])->withInput();
        }

        $days = $dateFrom->diffInDays($dateTo);

        Log::info('Reservation dates', [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'calculated_days' => $days,
            'min_nights' => $apartment->min_nights,
            'raw_input' => [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
            ],
        ]);

        if ($days <= 0) {
            Log::warning('Invalid date range', [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'calculated_days' => $days,
            ]);
            return back()->withErrors([
                'date_to' => __('frontpage.reservation.validation.after'),
            ])->withInput();
        }

        $allowedLocales = ['en', 'sr', 'ru'];
        $reservationLocale = app()->getLocale();
        if (! in_array($reservationLocale, $allowedLocales, true)) {
            $reservationLocale = (string) config('app.locale', 'en');
        }

        try {
            $reservation = $this->reservationService->createReservation($apartment, $data, $days, $reservationLocale);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        Log::info('Reservation created successfully', [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'email' => $reservation->email,
        ]);

        return back()->with('reservation_success', __('frontpage.reservation.success'));
    }
}
