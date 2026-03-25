<?php

namespace App\Services;

use App\Http\Repository\ReservationRepository;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
    ) {}

    public function parseDates(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::createFromFormat('Y-m-d', $dateFrom, 'UTC')->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $dateTo, 'UTC')->startOfDay();

        return [$from, $to];
    }

    public function createReservation(Apartment $apartment, array $data, int $days, string $locale): Reservation
    {
        return DB::transaction(function () use ($apartment, $data, $days, $locale) {
            $lockedApartment = $this->reservationRepository->lockApartmentForReservation($apartment->id);

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

            $priceDetails = $lockedApartment->calculatePrice($data['date_from'], $data['date_to']);
            $total = $priceDetails['total'];
            $depositRate = (float) config('website.deposit_rate', 0.3);
            $deposit = round($total * $depositRate, 2);

            $userId = $this->findOrCreateUser($data['email'], $data['name']);

            $reservationData = [
                'apartment_id' => $lockedApartment->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'locale' => $locale,
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

            return $this->reservationRepository->create($reservationData);
        }, 3);
    }

    private function findOrCreateUser(string $email, string $name): ?int
    {
        try {
            if (! in_array('user_id', (new Reservation)->getFillable())) {
                return null;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::withoutEvents(function () use ($name, $email) {
                    return User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                    ]);
                });

                $passwordResetToken = app('auth.password.broker')->createToken($user);
                Log::info('Password reset token generated for new user', [
                    'user_email' => $user->email,
                    'user_id' => $user->id,
                    'token_generated' => ! empty($passwordResetToken),
                ]);
            }

            return $user->id;
        } catch (\Exception $e) {
            Log::warning('Failed to create user for reservation: ' . $e->getMessage());

            return null;
        }
    }
}
