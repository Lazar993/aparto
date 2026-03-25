<?php

namespace App\Http\Repository;

use App\Models\Apartment;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReviewRepository
{
    public function getApprovedReviews(Apartment $apartment): Collection
    {
        return $apartment->approvedReviews()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function userHasReviewed(int $userId, int $apartmentId): bool
    {
        return Review::where('apartment_id', $apartmentId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getHostReviewStats(int $hostId): object
    {
        return Review::whereHas('apartment', fn (Builder $q) => $q->where('user_id', $hostId))
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as total, SUM(rating) as rating_sum')
            ->first();
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }
}
