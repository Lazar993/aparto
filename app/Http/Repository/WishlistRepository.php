<?php

namespace App\Http\Repository;

use App\Models\Apartment;
use App\Models\Wishlist;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WishlistRepository
{
    public function getUserWishlistIds(int $userId): array
    {
        return Wishlist::query()
            ->where('user_id', $userId)
            ->pluck('apartment_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function findEntry(int $userId, int $apartmentId): ?Wishlist
    {
        return Wishlist::query()
            ->where('user_id', $userId)
            ->where('apartment_id', $apartmentId)
            ->first();
    }

    public function create(int $userId, int $apartmentId): Wishlist
    {
        return Wishlist::create([
            'user_id' => $userId,
            'apartment_id' => $apartmentId,
        ]);
    }

    public function delete(Wishlist $entry): void
    {
        $entry->delete();
    }

    public function getWishlistApartments(int $userId, int $perPage): LengthAwarePaginator
    {
        return Apartment::query()
            ->withCount(['approvedReviews as reviews_count'])
            ->withAvg('approvedReviews as average_rating', 'rating')
            ->join('wishlists', function ($join) use ($userId) {
                $join->on('apartments.id', '=', 'wishlists.apartment_id')
                    ->where('wishlists.user_id', '=', $userId);
            })
            ->where('apartments.active', true)
            ->select('apartments.*')
            ->orderByDesc('wishlists.created_at')
            ->paginate($perPage, ['apartments.*'], 'wishlist_page');
    }
}
