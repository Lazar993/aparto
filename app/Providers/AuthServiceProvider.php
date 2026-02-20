<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Apartment::class => \App\Policies\ApartmentPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Reservation::class => \App\Policies\ReservationPolicy::class,
        \App\Models\Page::class => \App\Policies\PagePolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
