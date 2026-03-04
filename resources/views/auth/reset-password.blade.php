@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-auth-section">
        <div class="aparto-auth-container">
            <h1 class="aparto-auth-title">{{ __('Reset Password') }}</h1>

            @if ($errors->any())
                <div class="aparto-auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="aparto-auth-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="aparto-form-group">
                    <label for="email">{{ __('Email') }}</label>
                    <input 
                        type="email" 
                        id="email" 
                        value="{{ $email }}" 
                        disabled
                        class="aparto-input-disabled"
                    >
                </div>

                <div class="aparto-form-group">
                    <label for="password">{{ __('New Password') }}</label>
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
                    <small>{{ __('Minimum 8 characters') }}</small>
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
                        {{ __('Reset Password') }}
                    </button>
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
