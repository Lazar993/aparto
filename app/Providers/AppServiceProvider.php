<?php

namespace App\Providers;

use App\Models\Page;
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
        View::composer('layouts.partials.footer', function ($view): void {
            $footerPages = Page::where('is_active', true)
                ->orderBy('title')
                ->get(['title', 'slug']);

            $view->with('footerPages', $footerPages);
        });

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
