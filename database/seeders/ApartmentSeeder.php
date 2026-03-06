<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ApartmentSeeder extends Seeder
{
    public function run(): void
    {
        $leadImages = $this->getImagePaths('apartments/lead_images');
        $galleryImages = $this->getImagePaths('apartments/gallery_images');

        $userIds = User::query()->pluck('id');

        if ($userIds->isEmpty()) {
            $userIds = User::factory()->count(5)->create()->pluck('id');
        }

        $cities = [
            'Beograd',
            'Novi Sad',
            'Kragujevac',
            'Nis',
            'Subotica',
            'Leskovac',
            'Vranje',
            'Sombor',
            'Zrenjanin',
            'Kraljevo',
            'Raska',
        ];

        for ($i = 1; $i <= 50; $i++) {
            $city = Arr::random($cities);
            $minNights = fake()->numberBetween(1, 4);
            $discountNights = fake()->boolean(60)
                ? fake()->numberBetween(max(3, $minNights + 1), 10)
                : null;

            Apartment::query()->create([
                'user_id' => $userIds->random(),
                'title' => fake()->randomElement(['Modern Stay', 'City Escape', 'Cozy Retreat', 'Sunset Loft', 'Quiet Apartment']) . ' in ' . $city,
                'description' => fake()->paragraphs(2, true),
                'city' => $city,
                'address' => fake()->streetAddress(),
                'latitude' => fake()->latitude(42.0, 46.6),
                'longitude' => fake()->longitude(13.0, 19.5),
                'price_per_night' => fake()->numberBetween(45, 240),
                'min_nights' => $minNights,
                'discount_nights' => $discountNights,
                'discount_percentage' => $discountNights ? fake()->randomElement([5, 10, 12.5, 15, 20]) : null,
                'blocked_dates' => $this->generateBlockedDates(),
                'custom_pricing' => $this->generateCustomPricing(),
                'rooms' => fake()->numberBetween(1, 5),
                'guest_number' => fake()->numberBetween(1, 10),
                'active' => fake()->boolean(90),
                'parking' => fake()->boolean(55),
                'wifi' => fake()->boolean(95),
                'pet_friendly' => fake()->boolean(35),
                'lead_image' => $this->pickLeadImage($leadImages),
                'gallery_images' => $this->pickGalleryImages($galleryImages),
            ]);
        }
    }

    private function getImagePaths(string $directory): array
    {
        $absolutePath = storage_path('app/public/' . $directory);

        if (!File::isDirectory($absolutePath)) {
            return [];
        }

        return collect(File::files($absolutePath))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->map(fn ($file) => $directory . '/' . $file->getFilename())
            ->values()
            ->all();
    }

    private function pickLeadImage(array $leadImages): ?string
    {
        if (empty($leadImages)) {
            return null;
        }

        return Arr::random($leadImages);
    }

    private function pickGalleryImages(array $galleryImages): array
    {
        if (empty($galleryImages)) {
            return [];
        }

        $count = min(count($galleryImages), fake()->numberBetween(3, 6));

        return collect($galleryImages)
            ->shuffle()
            ->take($count)
            ->values()
            ->all();
    }

    private function generateBlockedDates(): ?array
    {
        if (!fake()->boolean(30)) {
            return null;
        }

        $from = fake()->dateTimeBetween('+2 weeks', '+6 months');
        $to = (clone $from)->modify('+' . fake()->numberBetween(2, 8) . ' days');

        return [[
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]];
    }

    private function generateCustomPricing(): ?array
    {
        if (!fake()->boolean(45)) {
            return null;
        }

        $from = fake()->dateTimeBetween('+1 week', '+5 months');
        $to = (clone $from)->modify('+' . fake()->numberBetween(3, 12) . ' days');

        return [[
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'price' => fake()->numberBetween(55, 280),
        ]];
    }
}
