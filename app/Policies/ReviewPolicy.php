<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Review;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view_any_review')
            || $this->hasPermission($user, 'view_review');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'view_review');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create_review');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'update_review');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'delete_review');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'delete_any_review');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'force_delete_review');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'force_delete_any_review');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'restore_review');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'restore_any_review');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Review $review): bool
    {
        if ($this->isHost($user) && ! $this->ownsReview($user, $review)) {
            return false;
        }

        return $this->hasPermission($user, 'replicate_review');
    }

    private function isHost(User $user): bool
    {
        return $user->isHost() || $user->hasRole('host');
    }

    private function ownsReview(User $user, Review $review): bool
    {
        if ($review->relationLoaded('apartment')) {
            return (int) $review->apartment?->user_id === (int) $user->id;
        }

        return $review->apartment()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasPermission($user, 'reorder_review');
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        foreach (['admin', 'web'] as $guard) {
            try {
                if ($user->hasPermissionTo($permission, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                // Permission may be stored under only one guard.
            }
        }

        return false;
    }
}
