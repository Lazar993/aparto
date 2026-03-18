@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-contact-section aparto-fade-up aparto-delay-1">
        <div class="aparto-contact-card">
            <h1 class="aparto-section-title">{{ __('frontpage.become_host.title') }}</h1>
            <p class="aparto-hero-subtitle">{{ __('frontpage.become_host.subtitle') }}</p>

            @if(session('success'))
                <div class="aparto-form-message is-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="aparto-form-message is-error">
                    <strong>{{ __('frontpage.become_host.error_title') }}</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="aparto-contact-form" method="POST" action="{{ route('become-host.submit') }}">
                @csrf
                <div class="aparto-contact-grid">
                    <div class="aparto-form-group">
                        <label for="host-name">{{ __('frontpage.become_host.form.name') }}</label>
                        <input id="host-name" type="text" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="aparto-form-group">
                        <label for="host-email">{{ __('frontpage.become_host.form.email') }}</label>
                        <input id="host-email" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="aparto-contact-grid">
                    <div class="aparto-form-group">
                        <label for="host-phone">{{ __('frontpage.become_host.form.phone') }}</label>
                        <input id="host-phone" type="tel" name="phone" value="{{ old('phone') }}" required>
                    </div>

                    <div class="aparto-form-group">
                        <label for="host-city">{{ __('frontpage.become_host.form.city') }}</label>
                        <input id="host-city" type="text" name="city" value="{{ old('city') }}" required>
                    </div>
                </div>

                <div class="aparto-form-group">
                    <label for="host-listing-url">{{ __('frontpage.become_host.form.listing_url') }}</label>
                    <input id="host-listing-url" type="url" name="listing_url" value="{{ old('listing_url') }}" placeholder="{{ __('frontpage.become_host.form.listing_url_placeholder') }}">
                </div>

                <div class="aparto-form-group">
                    <label for="host-apartments">{{ __('frontpage.become_host.form.number_of_apartments') }}</label>
                    <input id="host-apartments" type="number" name="number_of_apartments" value="{{ old('number_of_apartments') }}" min="1">
                </div>

                <div class="aparto-contact-captcha">
                    <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.site_key') }}"></div>
                </div>
                <script src="https://js.hcaptcha.com/1/api.js?hl={{ str_replace('_', '-', app()->getLocale()) }}" async defer></script>

                <button type="submit" class="aparto-button primary">{{ __('frontpage.become_host.form.submit') }}</button>
            </form>
        </div>
    </section>
@endsection
