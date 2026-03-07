<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservationSeeder extends Seeder
{
    private const RESERVATIONS_COUNT = 70;

    public function run(): void
    {
        $apartments = Apartment::query()->where('active', true)->get();

        if ($apartments->isEmpty()) {
            return;
        }

        $guestUsers = $this->resolveGuestUsers();

        Reservation::withoutEvents(function () use ($apartments, $guestUsers): void {
            $created = 0;
            $attempts = 0;
            $maxAttempts = self::RESERVATIONS_COUNT * 20;

            while ($created < self::RESERVATIONS_COUNT && $attempts < $maxAttempts) {
                $attempts++;

                $apartment = $apartments->random();
                $status = $this->pickStatus();
                [$dateFrom, $dateTo, $createdAt] = $this->buildDateRange($status, (int) $apartment->min_nights);

                if ($status !== 'canceled' && !$apartment->isAvailable($dateFrom, $dateTo)) {
                    continue;
                }

                $this->createReservation($apartment, $guestUsers, $status, $dateFrom, $dateTo, $createdAt);
                $created++;
            }

            // Ensure target count is reached even if availability gets tight.
            while ($created < self::RESERVATIONS_COUNT) {
                $apartment = $apartments->random();
                [$dateFrom, $dateTo, $createdAt] = $this->buildDateRange('canceled', (int) $apartment->min_nights);

                $this->createReservation($apartment, $guestUsers, 'canceled', $dateFrom, $dateTo, $createdAt);
                $created++;
            }
        });

        $this->syncReservationsCountByApartment();
    }

    private function resolveGuestUsers(): Collection
    {
        $minimumGuests = 36;

        $guests = User::query()
            ->where('user_type', User::TYPE_FRONT)
            ->get();

        if ($guests->count() >= $minimumGuests) {
            return $guests;
        }

        User::factory()
            ->count($minimumGuests - $guests->count())
            ->create([
                'user_type' => User::TYPE_FRONT,
            ]);

        return User::query()
            ->where('user_type', User::TYPE_FRONT)
            ->get();
    }

    private function pickStatus(): string
    {
        $roll = fake()->numberBetween(1, 100);

        if ($roll <= 58) {
            return 'confirmed';
        }

        if ($roll <= 83) {
            return 'pending';
        }

        return 'canceled';
    }

    /**
     * @return array{0:string,1:string,2:Carbon}
     */
    private function buildDateRange(string $status, int $minNights): array
    {
        $nights = fake()->numberBetween(max(1, $minNights), max(2, $minNights + 8));

        if ($status === 'confirmed') {
            $from = fake()->boolean(62)
                ? Carbon::instance(fake()->dateTimeBetween('-180 days', '-5 days'))->startOfDay()
                : Carbon::instance(fake()->dateTimeBetween('+4 days', '+110 days'))->startOfDay();
        } elseif ($status === 'pending') {
            $from = Carbon::instance(fake()->dateTimeBetween('+3 days', '+140 days'))->startOfDay();
        } else {
            $from = Carbon::instance(fake()->dateTimeBetween('-120 days', '+120 days'))->startOfDay();
        }

        $to = (clone $from)->addDays($nights);

        $createdWindowEnd = $from->copy()->subDay();
        if ($createdWindowEnd->gt(now())) {
            $createdWindowEnd = now()->copy();
        }

        $createdWindowStart = $createdWindowEnd->copy()->subDays(45);

        $createdAt = Carbon::instance(
            fake()->dateTimeBetween($createdWindowStart, $createdWindowEnd)
        );

        if ($createdAt->isFuture()) {
            $createdAt = now()->copy()->subHours(fake()->numberBetween(4, 72));
        }

        return [$from->toDateString(), $to->toDateString(), $createdAt];
    }

    private function createReservation(
        Apartment $apartment,
        Collection $guestUsers,
        string $status,
        string $dateFrom,
        string $dateTo,
        Carbon $createdAt
    ): void {
        $pricing = $apartment->calculatePrice($dateFrom, $dateTo);

        $customer = $status === 'confirmed'
            ? $guestUsers->random()
            : (fake()->boolean(70) ? $guestUsers->random() : null);

        $pricePerNight = (float) ($pricing['price_per_night'] ?? $apartment->price_per_night);
        $nights = max(1, (int) ($pricing['nights'] ?? 1));
        $totalPrice = max(30, (float) ($pricing['total'] ?? ($pricePerNight * $nights)));

        $paymentProvider = null;
        $paymentReference = null;
        $paidAt = null;

        if ($status === 'confirmed') {
            $paymentProvider = fake()->randomElement(['stripe', 'paypal', 'bank_transfer', 'cash']);
            $paymentReference = strtoupper(fake()->bothify('PAY-####??##'));

            if ($paymentProvider !== 'cash') {
                $paidAt = Carbon::instance(fake()->dateTimeBetween($createdAt, now()));
            }
        } elseif ($status === 'pending' && fake()->boolean(35)) {
            $paymentProvider = fake()->randomElement(['stripe', 'paypal']);
            $paymentReference = strtoupper(fake()->bothify('PND-####??##'));
        }

        $deposit = null;
        if (in_array($status, ['confirmed', 'pending'], true) && fake()->boolean(75)) {
            $deposit = round($totalPrice * fake()->randomFloat(2, 0.2, 0.4), 2);
        }

        $reservation = new Reservation([
            'apartment_id' => $apartment->id,
            'user_id' => $customer?->id,
            'name' => $customer?->name ?? fake()->name(),
            'email' => $customer?->email ?? fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'nights' => $nights,
            'price_per_night' => round($pricePerNight, 2),
            'total_price' => round($totalPrice, 2),
            'deposit_amount' => $deposit,
            'status' => $status,
            'payment_provider' => $paymentProvider,
            'payment_reference' => $paymentReference,
            'paid_at' => $paidAt,
            'note' => fake()->boolean(18)
                ? fake()->randomElement([
                    'Late check-in expected around 22:00.',
                    'Traveling with toddler, needs baby crib if available.',
                    'Business stay, invoice requested.',
                    'Coming by car and needs parking details.',
                ])
                : null,
            'reservations_count' => 0,
            'review_reminder_sent_at' => null,
        ]);

        $reservation->created_at = $createdAt;
        $reservation->updated_at = (clone $createdAt)->addHours(fake()->numberBetween(1, 72));
        $reservation->saveQuietly();
    }

    private function syncReservationsCountByApartment(): void
    {
        DB::table('reservations')->update(['reservations_count' => 0]);

        $counts = Reservation::query()
            ->selectRaw('apartment_id, COUNT(*) as total')
            ->where('status', 'confirmed')
            ->groupBy('apartment_id')
            ->pluck('total', 'apartment_id');

        foreach ($counts as $apartmentId => $total) {
            DB::table('reservations')
                ->where('apartment_id', $apartmentId)
                ->update(['reservations_count' => (int) $total]);
        }
    }
}
