<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ReservationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view_any_reservation')
            || $this->hasPermission($user, 'view_reservation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'view_reservation');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create_reservation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'update_reservation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'delete_reservation');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'delete_any_reservation');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'force_delete_reservation');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'force_delete_any_reservation');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'restore_reservation');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'restore_any_reservation');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Reservation $reservation): bool
    {
        return $this->hasPermission($user, 'replicate_reservation');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasPermission($user, 'reorder_reservation');
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        foreach (['admin', 'web'] as $guard) {
            try {
                if ($user->hasPermissionTo($permission, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                // Ignore missing permission in one guard and keep checking the other.
            }
        }

        return false;
    }
}
