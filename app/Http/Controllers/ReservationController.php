<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function myReservations(Request $request)
    {
        $user = $request->user();

        $reservations = Reservation::query()
            ->with('apartment')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($emailQuery) use ($user) {
                        $emailQuery->whereNull('user_id')
                            ->where('email', $user->email);
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(config('website.apartments_per_page'));

        $wishlistApartmentIds = Wishlist::query()
            ->where('user_id', (int) $user->id)
            ->pluck('apartment_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $wishlistApartments = Apartment::query()
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->join('wishlists', function ($join) use ($user) {
                $join->on('apartments.id', '=', 'wishlists.apartment_id')
                    ->where('wishlists.user_id', '=', (int) $user->id);
            })
            ->where('apartments.active', true)
            ->select('apartments.*')
            ->orderByDesc('wishlists.created_at')
            ->paginate((int) config('website.apartments_per_page', 12), ['apartments.*'], 'wishlist_page');

        return view('frontend.my-profile', compact('reservations', 'wishlistApartments', 'wishlistApartmentIds'));
    }

    public function store(Request $request, Apartment $apartment)
    {
        $siteKey = (string) config('services.hcaptcha.site_key');
        $secret = (string) config('services.hcaptcha.secret');

        if ($siteKey === '' || $secret === '') {
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

        [$captchaSuccess, $captchaErrors] = $this->verifyHcaptchaToken(
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

        // Parse dates explicitly in UTC to avoid timezone issues
        // Dates from frontend are already in Y-m-d format (e.g., '2026-03-15')
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

        // Log for debugging
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

        // Check if dates are valid
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
            $reservation = DB::transaction(function () use ($apartment, $data, $days, $reservationLocale) {
                // Lock apartment row so two checkout requests for the same apartment cannot pass availability together.
                $lockedApartment = Apartment::query()
                    ->whereKey($apartment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedApartment->isAvailable($data['date_from'], $data['date_to'])) {
                    throw ValidationException::withMessages([
                        'date_from' => __('frontpage.reservation.validation.unavailable'),
                    ]);
                }

                if ($lockedApartment->min_nights && $days < $lockedApartment->min_nights) {
                    Log::warning('Minimum nights not met', [
                        'apartment_id' => $lockedApartment->id,
                        'required_nights' => $lockedApartment->min_nights,
                        'selected_nights' => $days,
                    ]);

                    throw ValidationException::withMessages([
                        'date_to' => __('frontpage.reservation.validation.min_nights', ['min' => $lockedApartment->min_nights])
                            . ' ' . __('You selected :selected nights.', ['selected' => $days]),
                    ]);
                }

                // Calculate price inside transaction to keep it consistent with locked apartment data.
                $priceDetails = $lockedApartment->calculatePrice($data['date_from'], $data['date_to']);
                $total = $priceDetails['total'];
                $depositRate = (float) config('website.deposit_rate', 0.3);
                $deposit = round($total * $depositRate, 2);

                // Find or create user (only if user_id column exists in reservations table)
                $userId = null;

                try {
                    // Check if user_id column exists by checking the fillable array includes it
                    if (in_array('user_id', (new Reservation)->getFillable())) {
                        $user = User::where('email', $data['email'])->first();

                        if (!$user) {
                            // Create new user with random password (without firing events to prevent duplicate emails)
                            $user = User::withoutEvents(function () use ($data) {
                                return User::create([
                                    'name' => $data['name'],
                                    'email' => $data['email'],
                                    'password' => Hash::make(Str::random(32)), // Random password
                                ]);
                            });

                            $passwordResetToken = app('auth.password.broker')->createToken($user);
                            Log::info('Password reset token generated for new user', [
                                'user_email' => $user->email,
                                'user_id' => $user->id,
                                'token_generated' => !empty($passwordResetToken),
                            ]);
                        }

                        $userId = $user->id;
                    }
                } catch (\Exception $e) {
                    // If user creation fails, continue without user_id
                    Log::warning('Failed to create user for reservation: ' . $e->getMessage());
                }

                $reservationData = [
                    'apartment_id' => $lockedApartment->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'locale' => $reservationLocale,
                    'phone' => $data['phone'],
                    'date_from' => $data['date_from'],
                    'date_to' => $data['date_to'],
                    'nights' => $days,
                    'price_per_night' => $priceDetails['price_per_night'],
                    'total_price' => $total,
                    'deposit_amount' => $deposit,
                    'note' => $data['note'] ?? null,
                ];

                if ($userId) {
                    $reservationData['user_id'] = $userId;
                }

                return Reservation::create($reservationData);
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        Log::info('Reservation created successfully', [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'email' => $reservation->email,
        ]);

        return back()->with('success', __('frontpage.reservation.success'));
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
            Log::warning('hCaptcha verification request failed for reservation form.', [
                'ip' => $ip,
                'error' => $exception->getMessage(),
            ]);

            return [false, ['request-failed']];
        }
    }

}
