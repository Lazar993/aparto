<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): Response
    {
        $this->applyLocaleFromSession($request);

        return parent::render($request, $e);
    }

    private function applyLocaleFromSession(Request $request): void
    {
        if ($request->hasSession()) {
            $locale = $request->session()->get('locale');
        } else {
            $request->setLaravelSession(app('session')->driver());
            $locale = $request->session()->get('locale');
        }

        $allowed = ['en', 'sr', 'ru'];

        if ($locale && in_array($locale, $allowed, true)) {
            App::setLocale($locale);
        }
    }
}
