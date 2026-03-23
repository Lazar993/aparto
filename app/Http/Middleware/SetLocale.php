<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    private const ALLOWED = ['sr', 'en', 'ru'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');

        if (! is_string($locale) || ! in_array($locale, self::ALLOWED, true)) {
            $locale = $request->session()->get('locale', 'sr');
        }

        if (! in_array($locale, self::ALLOWED, true)) {
            $locale = 'sr';
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
