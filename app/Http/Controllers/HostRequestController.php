<?php

namespace App\Http\Controllers;

use App\Models\HostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HostRequestController extends Controller
{
    public function show(): View
    {
        return view('frontend.become-host');
    }

    public function submit(Request $request)
    {
        $siteKey = (string) config('services.hcaptcha.site_key');
        $secret = (string) config('services.hcaptcha.secret');

        if ($siteKey === '' || $secret === '') {
            Log::warning('hCaptcha configuration is missing for contact form.');

            return back()
                ->withInput()
                ->withErrors(['hcaptcha' => __('frontpage.contact_page.validation.captcha_unavailable')]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,30}$/'],
            'city' => ['required', 'string', 'max:100'],
            'listing_url' => ['nullable', 'url', 'max:500'],
            'number_of_apartments' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'h-captcha-response' => ['required', 'string'],
        ], [
            'name.required' => __('frontpage.become_host.validation.required', ['attribute' => __('frontpage.become_host.form.name')]),
            'email.required' => __('frontpage.become_host.validation.required', ['attribute' => __('frontpage.become_host.form.email')]),
            'email.email' => __('frontpage.become_host.validation.email'),
            'phone.required' => __('frontpage.become_host.validation.required', ['attribute' => __('frontpage.become_host.form.phone')]),
            'phone.min' => __('frontpage.become_host.validation.phone'),
            'city.required' => __('frontpage.become_host.validation.required', ['attribute' => __('frontpage.become_host.form.city')]),
            'listing_url.url' => __('frontpage.become_host.validation.url'),
            'number_of_apartments.integer' => __('frontpage.become_host.validation.integer'),
            'number_of_apartments.min' => __('frontpage.become_host.validation.integer'),
            'h-captcha-response.required' => __('frontpage.contact_page.validation.captcha_required'),
        ]);

        try {
            $locale = app()->getLocale();
            if (! in_array($locale, ['sr', 'en', 'ru'], true)) {
                $locale = (string) config('app.locale', 'sr');
            }

            $hostRequest = HostRequest::create(array_merge($data, ['locale' => $locale]));

            \Illuminate\Support\Facades\Notification::route('mail', config('website.contact_email'))
                ->notify(new \App\Notifications\HostRequestCreatedForAdmin($hostRequest));
        } catch (\Throwable $e) {
            Log::error('Host request submission failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => __('frontpage.become_host.send_error')]);
        }

        return redirect()->route('become-host.show')
            ->with('success', __('frontpage.become_host.success'));
    }
}
