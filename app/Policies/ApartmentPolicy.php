<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Apartment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ApartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view_any_apartment')
            || $this->hasPermission($user, 'view_apartment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'view_apartment');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create_apartment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'update_apartment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'delete_apartment');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'delete_any_apartment');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'force_delete_apartment');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'force_delete_any_apartment');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'restore_apartment');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'restore_any_apartment');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Apartment $apartment): bool
    {
        return $this->hasPermission($user, 'replicate_apartment');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $this->hasPermission($user, 'reorder_apartment');
    }

    protected function hasPermission(User $user, string $permission): bool
    {
        foreach (['admin', 'web'] as $guard) {
            try {
                if ($user->hasPermissionTo($permission, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                // Permission may only be defined for one guard.
            }
        }

        return false;
    }
}
