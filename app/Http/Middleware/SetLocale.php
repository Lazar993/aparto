<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', 'sr');
        $allowed = ['en', 'sr', 'ru'];

        if (! in_array($locale, $allowed, true)) {
            $locale = 'sr';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
