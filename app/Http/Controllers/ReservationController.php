<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationCreated;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function store(Request $request, Apartment $apartment)
    {
        $rules = [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after:date_from'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'regex:/^\d{9,}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        $messages = [
            'required' => __('frontpage.reservation.validation.required'),
            'date' => __('frontpage.reservation.validation.date'),
            'date_to.after' => __('frontpage.reservation.validation.after'),
            'email' => __('frontpage.reservation.validation.email'),
            'phone.regex' => __('frontpage.reservation.validation.phone'),
        ];

        $attributes = trans('frontpage.reservation.attributes');

        $data = $request->validate($rules, $messages, $attributes);

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

        if (! $apartment->isAvailable($data['date_from'], $data['date_to'])) {
            return back()->withErrors([
                'date_from' => __('frontpage.reservation.validation.unavailable'),
            ])->withInput();
        }

        // Check minimum nights requirement
        if ($apartment->min_nights && $days < $apartment->min_nights) {
            Log::warning('Minimum nights not met', [
                'apartment_id' => $apartment->id,
                'required_nights' => $apartment->min_nights,
                'selected_nights' => $days,
            ]);
            
            return back()->withErrors([
                'date_to' => __('frontpage.reservation.validation.min_nights', ['min' => $apartment->min_nights]) 
                    . ' ' . __('You selected :selected nights.', ['selected' => $days]),
            ])->withInput();
        }

        // Calculate price using the new method that considers custom pricing and discounts
        $priceDetails = $apartment->calculatePrice($data['date_from'], $data['date_to']);
        $total = $priceDetails['total'];
        $depositRate = (float) config('website.deposit_rate', 0.3);
        $deposit = round($total * $depositRate, 2);

        // Find or create user (only if user_id column exists in reservations table)
        $userId = null;
        
        try {
            // Check if user_id column exists by checking the fillable array includes it
            if (in_array('user_id', (new Reservation)->getFillable())) {
                $user = User::where('email', $data['email'])->first();
                $isNewUser = false;

                if (!$user) {
                    // Create new user with random password
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(32)), // Random password
                    ]);

                    $isNewUser = true;

                    // Fire the Registered event to send verification email
                    event(new Registered($user));
                }

                $userId = $user->id;

                // Send password reset link for new users
                if ($isNewUser) {
                    try {
                        $token = app('auth.password.broker')->createToken($user);
                        $user->sendPasswordResetNotification($token);
                        Log::info('Password reset email sent successfully', [
                            'user_email' => $user->email,
                            'user_id' => $user->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send password reset email', [
                            'error' => $e->getMessage(),
                            'user_email' => $user->email,
                        ]);
                        // Don't fail reservation if email fails
                    }
                }
            }
        } catch (\Exception $e) {
            // If user creation fails, continue without user_id
            Log::warning('Failed to create user for reservation: ' . $e->getMessage());
        }

        $reservationData = [
            'apartment_id' => $apartment->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'nights' => $days,
            'price_per_night' => $priceDetails['price_per_night'],
            'total_price' => $total,
            'deposit_amount' => $deposit,
            'note' => $data['note'] ?? null,
        ];

        // Add user_id only if we have one
        if ($userId) {
            $reservationData['user_id'] = $userId;
        }

        $reservation = Reservation::create($reservationData);

        // Send reservation confirmation email
        try {
            if ($userId) {
                // Send to registered user
                $user = User::find($userId);
                $user->notify(new ReservationCreated($reservation, $isNewUser ?? false));
                Log::info('Reservation confirmation email sent', [
                    'reservation_id' => $reservation->id,
                    'user_email' => $user->email,
                ]);
            } else {
                // Send to guest email (no user account)
                Notification::route('mail', $reservationData['email'])
                    ->notify(new ReservationCreated($reservation, false));
                Log::info('Reservation confirmation email sent to guest', [
                    'reservation_id' => $reservation->id,
                    'email' => $reservationData['email'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send reservation confirmation email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);
            // Don't fail the reservation if email fails
        }

        return back()->with('success', __('frontpage.reservation.success'));
    }

}
