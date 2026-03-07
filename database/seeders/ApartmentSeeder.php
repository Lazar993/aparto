<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class ApartmentSeeder extends Seeder
{
    private const APARTMENTS_COUNT = 50;

    public function run(): void
    {
        $leadImages = $this->getImagePaths('apartments/lead_images');
        $galleryImages = $this->getImagePaths('apartments/gallery_images');

        $hostIds = $this->resolveHostIds();

        $minimumHosts = 12;
        if ($hostIds->count() < $minimumHosts) {
            $this->createAdditionalHosts($minimumHosts - $hostIds->count());
            $hostIds = $this->resolveHostIds();
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
            'Zlatibor',
            'Kopaonik',
            'Pirot',
        ];

        $titlePrefixes = ['Modern', 'Cozy', 'Urban', 'Quiet', 'Panorama', 'Central', 'Family', 'Premium'];
        $titleTypes = ['Studio', 'Apartment', 'Loft', 'Suite', 'Retreat', 'Residence'];

        for ($i = 1; $i <= self::APARTMENTS_COUNT; $i++) {
            $city = Arr::random($cities);
            $rooms = fake()->numberBetween(1, 5);
            $guestNumber = fake()->numberBetween(max(2, $rooms), min(10, $rooms * 2 + 2));
            $basePrice = fake()->numberBetween(38, 260);
            $minNights = fake()->numberBetween(1, min(5, $rooms + 1));
            $discountNights = fake()->boolean(60)
                ? fake()->numberBetween(max(3, $minNights + 1), 14)
                : null;

            Apartment::query()->create([
                'user_id' => $hostIds->random(),
                'title' => Arr::random($titlePrefixes) . ' ' . Arr::random($titleTypes) . ' in ' . $city,
                'description' => fake()->paragraphs(fake()->numberBetween(2, 4), true),
                'city' => $city,
                'address' => fake()->streetAddress(),
                'latitude' => fake()->latitude(42.0, 46.6),
                'longitude' => fake()->longitude(13.0, 19.5),
                'price_per_night' => $basePrice,
                'min_nights' => $minNights,
                'discount_nights' => $discountNights,
                'discount_percentage' => $discountNights ? fake()->randomElement([5, 10, 12.5, 15, 18, 20]) : null,
                'blocked_dates' => $this->generateBlockedDates(),
                'custom_pricing' => $this->generateCustomPricing(),
                'rooms' => $rooms,
                'guest_number' => $guestNumber,
                'active' => fake()->boolean(88),
                'parking' => fake()->boolean(60),
                'wifi' => fake()->boolean(95),
                'pet_friendly' => fake()->boolean(30),
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

    private function resolveHostIds(): Collection
    {
        $hosts = User::query()
            ->where('user_type', User::TYPE_HOST);

        if ($this->rolesAvailable()) {
            $hosts->whereHas('roles', function ($query): void {
                $query->where('name', 'host');
            });
        }

        return $hosts->pluck('id');
    }

    private function createAdditionalHosts(int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $createdHosts = User::factory()
            ->count($count)
            ->create([
                'user_type' => User::TYPE_HOST,
                'email_verified_at' => now(),
            ]);

        if (! $this->rolesAvailable()) {
            return;
        }

        $hostWeb = Role::findOrCreate('host', 'web');
        $hostAdmin = Role::findOrCreate('host', 'admin');

        $createdHosts->each(function (User $host) use ($hostWeb, $hostAdmin): void {
            $host->syncRoles([$hostWeb, $hostAdmin]);
        });
    }

    private function rolesAvailable(): bool
    {
        return Schema::hasTable('roles') && Schema::hasTable('model_has_roles');
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

        $periods = [];
        $periodCount = fake()->numberBetween(1, 2);

        for ($i = 0; $i < $periodCount; $i++) {
            $from = fake()->dateTimeBetween('+' . (14 + ($i * 20)) . ' days', '+' . (130 + ($i * 20)) . ' days');
            $to = (clone $from)->modify('+' . fake()->numberBetween(2, 7) . ' days');

            $periods[] = [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ];
        }

        return $periods;
    }

    private function generateCustomPricing(): ?array
    {
        if (!fake()->boolean(45)) {
            return null;
        }

        $periods = [];
        $periodCount = fake()->numberBetween(1, 3);

        for ($i = 0; $i < $periodCount; $i++) {
            $from = fake()->dateTimeBetween('+' . (7 + ($i * 15)) . ' days', '+' . (150 + ($i * 20)) . ' days');
            $to = (clone $from)->modify('+' . fake()->numberBetween(3, 10) . ' days');

            $periods[] = [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'price' => fake()->numberBetween(50, 320),
            ];
        }

        return $periods;
    }
}
