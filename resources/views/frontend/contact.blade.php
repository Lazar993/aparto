@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-contact-section aparto-fade-up aparto-delay-1">
        <div class="aparto-contact-card">
            <h1 class="aparto-section-title">{{ __('frontpage.contact_page.title') }}</h1>
            <p class="aparto-hero-subtitle">{{ __('frontpage.contact_page.subtitle') }}</p>

            @if(session('success'))
                <div class="aparto-form-message is-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="aparto-form-message is-error">
                    <strong>{{ __('frontpage.contact_page.error_title') }}</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="aparto-contact-form" method="POST" action="{{ route('contact.submit') }}">
                @csrf
                <div class="aparto-contact-grid">
                    <div class="aparto-form-group">
                        <label for="contact-name">{{ __('frontpage.contact_page.form.name') }}</label>
                        <input id="contact-name" type="text" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="aparto-form-group">
                        <label for="contact-surname">{{ __('frontpage.contact_page.form.surname') }}</label>
                        <input id="contact-surname" type="text" name="surname" value="{{ old('surname') }}" required>
                    </div>
                </div>

                <div class="aparto-form-group">
                    <label for="contact-email">{{ __('frontpage.contact_page.form.email') }}</label>
                    <input id="contact-email" type="email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="aparto-form-group">
                    <label for="contact-message">{{ __('frontpage.contact_page.form.message') }}</label>
                    <textarea id="contact-message" name="message" rows="6" required>{{ old('message') }}</textarea>
                </div>

                <div class="aparto-contact-captcha">
                    <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.site_key') }}"></div>
                </div>
                <script src="https://js.hcaptcha.com/1/api.js" async defer></script>

                <button type="submit" class="aparto-button primary">{{ __('frontpage.contact_page.form.submit') }}</button>
            </form>
        </div>
    </section>
@endsection
