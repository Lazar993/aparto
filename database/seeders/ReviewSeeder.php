<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ReviewSeeder extends Seeder
{
    private const REVIEWS_COUNT = 90;

    public function run(): void
    {
        $confirmedReservations = Reservation::query()
            ->where('status', 'confirmed')
            ->whereNotNull('user_id')
            ->whereDate('date_to', '<=', now())
            ->whereHas('user', function ($query): void {
                $query->where('user_type', User::TYPE_FRONT);
            })
            ->get(['apartment_id', 'user_id', 'date_to'])
            ->groupBy('apartment_id');

        if ($confirmedReservations->isEmpty()) {
            return;
        }

        $apartments = Apartment::query()
            ->where('active', true)
            ->whereIn('id', $confirmedReservations->keys())
            ->get();

        if ($apartments->isEmpty()) {
            return;
        }

        $created = 0;

        // Seed a recent, approved baseline so homepage "best rated" sections are populated.
        $featuredApartments = $apartments
            ->shuffle()
            ->take(min(8, $apartments->count()));

        foreach ($featuredApartments as $apartment) {
            for ($i = 0; $i < 3 && $created < self::REVIEWS_COUNT; $i++) {
                $reservation = $this->pickReservationForApartment($apartment->id, $confirmedReservations);

                if (!$reservation) {
                    continue;
                }

                $rating = fake()->randomElement([4, 4, 5, 5, 5]);
                $createdAt = $this->buildCreatedAt($reservation, 'approved');

                $this->createReview($apartment->id, (int) $reservation->user_id, 'approved', $rating, $createdAt);
                $created++;
            }
        }

        while ($created < self::REVIEWS_COUNT) {
            $apartment = $apartments->random();
            $status = $this->pickStatus();
            $rating = $this->pickRating($status);
            $reservation = $this->pickReservationForApartment($apartment->id, $confirmedReservations);

            if (!$reservation) {
                continue;
            }

            $createdAt = $this->buildCreatedAt($reservation, $status);

            $this->createReview($apartment->id, (int) $reservation->user_id, $status, $rating, $createdAt);
            $created++;
        }
    }

    private function pickStatus(): string
    {
        $roll = fake()->numberBetween(1, 100);

        if ($roll <= 74) {
            return 'approved';
        }

        if ($roll <= 90) {
            return 'pending';
        }

        return 'rejected';
    }

    private function pickRating(string $status): int
    {
        if ($status === 'approved') {
            return fake()->randomElement([3, 4, 4, 4, 5, 5, 5]);
        }

        if ($status === 'pending') {
            return fake()->randomElement([3, 4, 4, 5]);
        }

        return fake()->randomElement([1, 2, 2, 3, 3, 4]);
    }

    private function pickReservationForApartment(int $apartmentId, Collection $confirmedReservations): ?object
    {
        $reservationRows = $confirmedReservations->get($apartmentId);

        if (!$reservationRows instanceof Collection || $reservationRows->isEmpty()) {
            return null;
        }

        return $reservationRows->random();
    }

    private function buildCreatedAt(object $reservation, string $status): Carbon
    {
        $dateTo = Carbon::parse($reservation->date_to)->startOfDay();
        $from = $dateTo->copy()->addDay();
        $maxDate = match ($status) {
            'approved' => $dateTo->copy()->addDays(45),
            'pending' => $dateTo->copy()->addDays(20),
            default => $dateTo->copy()->addDays(60),
        };

        if ($maxDate->gt(now())) {
            $maxDate = now()->copy();
        }

        if ($from->gt($maxDate)) {
            return now()->copy()->subDays(fake()->numberBetween(1, 10));
        }

        return Carbon::instance(fake()->dateTimeBetween($from, $maxDate));
    }

    private function createReview(int $apartmentId, int $userId, string $status, int $rating, Carbon $createdAt): void
    {
        $review = new Review([
            'apartment_id' => $apartmentId,
            'user_id' => $userId,
            'rating' => $rating,
            'comment' => $this->buildComment($rating, $status),
            'status' => $status,
        ]);

        $review->created_at = $createdAt;
        $review->updated_at = (clone $createdAt)->addHours(fake()->numberBetween(2, 96));
        $review->saveQuietly();
    }

    private function buildComment(int $rating, string $status): string
    {
        if ($status === 'rejected') {
            return fake()->randomElement([
                'The apartment looked different from the listing photos and check-in communication was confusing.',
                'Noise at night and cleanliness issues made this stay disappointing for us.',
                'Location was good, but maintenance problems were not solved during our stay.',
            ]);
        }

        if ($rating >= 5) {
            return fake()->randomElement([
                'Excellent host and very clean place. The location made city exploration easy.',
                'Everything was exactly as advertised. Fast communication and smooth check-in.',
                'One of our best stays in Serbia. Comfortable beds, strong Wi-Fi, and great value.',
            ]);
        }

        if ($rating >= 4) {
            return fake()->randomElement([
                'Great apartment overall, especially for a weekend stay. Would book again.',
                'Solid experience with good amenities. Minor details could be improved.',
                'Very good value for price and convenient neighborhood.',
            ]);
        }

        if ($rating >= 3) {
            return fake()->randomElement([
                'Decent stay and fair price, but a few things felt below expectations.',
                'Apartment was okay for a short trip, though check-in took longer than expected.',
                'Average overall experience. Nothing major, but room for improvement.',
            ]);
        }

        return fake()->randomElement([
            'Our stay did not meet expectations due to comfort and cleanliness issues.',
            'Communication and apartment condition both need significant improvement.',
        ]);
    }
}
