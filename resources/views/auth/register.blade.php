@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-auth-section">
        <div class="aparto-auth-container">
            <h1 class="aparto-auth-title">{{ __('Create account') }}</h1>

            @php
                $wishlistNotice = request()->query('notice') === 'wishlist';
                $loginUrl = request()->filled('redirect')
                    ? route('login', [
                        'redirect' => request()->query('redirect'),
                        'notice' => $wishlistNotice ? 'wishlist' : null,
                    ])
                    : route('login');
            @endphp

            @if ($wishlistNotice)
                <div class="aparto-auth-notice">
                    {{ __('frontpage.auth.wishlist_notice') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="aparto-auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" class="aparto-auth-form">
                @csrf

                <div class="aparto-form-group">
                    <label for="name">{{ __('Name') }}</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                    >
                </div>

                <div class="aparto-form-group">
                    <label for="email">{{ __('Email') }}</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="aparto-form-group">
                    <label for="password">{{ __('Password') }}</label>
                    <div class="aparto-password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            minlength="8"
                        >
                        <button
                            type="button"
                            class="aparto-password-toggle"
                            data-password-toggle
                            data-target="password"
                            aria-label="Show password"
                            title="Show password"
                        >
                            <span class="aparto-eye-open" aria-hidden="true">👁</span>
                            <span class="aparto-eye-closed is-hidden" aria-hidden="true">🙈</span>
                        </button>
                    </div>
                </div>

                <div class="aparto-form-group">
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <div class="aparto-password-wrap">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            minlength="8"
                        >
                        <button
                            type="button"
                            class="aparto-password-toggle"
                            data-password-toggle
                            data-target="password_confirmation"
                            aria-label="Show password"
                            title="Show password"
                        >
                            <span class="aparto-eye-open" aria-hidden="true">👁</span>
                            <span class="aparto-eye-closed is-hidden" aria-hidden="true">🙈</span>
                        </button>
                    </div>
                </div>

                <div class="aparto-form-actions">
                    <button type="submit" class="aparto-btn-primary">
                        {{ __('Create account') }}
                    </button>
                    <a href="{{ $loginUrl }}" class="aparto-link">
                        {{ __('Already have an account? Login') }}
                    </a>
                </div>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggles = document.querySelectorAll('[data-password-toggle]');

            toggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    var targetId = toggle.getAttribute('data-target');
                    var input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    var isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';

                    var openIcon = toggle.querySelector('.aparto-eye-open');
                    var closedIcon = toggle.querySelector('.aparto-eye-closed');

                    if (openIcon && closedIcon) {
                        openIcon.classList.toggle('is-hidden', isPassword);
                        closedIcon.classList.toggle('is-hidden', !isPassword);
                    }

                    var nextLabel = isPassword ? 'Hide password' : 'Show password';
                    toggle.setAttribute('aria-label', nextLabel);
                    toggle.setAttribute('title', nextLabel);
                });
            });
        });
    </script>
@endsection
