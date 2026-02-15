<?php

namespace App\Providers;

use App\Models\Page;
use Filament\Facades\Filament;
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
        View::composer('layouts.partials.footer', function ($view): void {
            $footerPages = Page::where('is_active', true)
                ->orderBy('title')
                ->get(['title', 'slug']);

            $view->with('footerPages', $footerPages);
        });

        Filament::serving(function (): void {
            Filament::registerScripts([
                Vite::asset('resources/js/filament-osm.js'),
            ], true);
        });

        if(config('app.env') === 'production'){
            \Illuminate\Support\Facades\URL::forceScheme('https');
        } else {
            \Illuminate\Support\Facades\URL::forceScheme('http');
        }

    }
}
