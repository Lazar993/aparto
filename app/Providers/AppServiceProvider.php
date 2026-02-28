<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Reservation;
use App\Observers\ReservationObserver;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Reservation::observe(ReservationObserver::class);
        
        FilamentAsset::register([
            Js::make('filament-osm', Vite::asset('resources/js/filament-osm.js')),
        ]);

        if(config('app.env') === 'production'){
            \Illuminate\Support\Facades\URL::forceScheme('https');
        } else {
            \Illuminate\Support\Facades\URL::forceScheme('http');
        }
    }
}
