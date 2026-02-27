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
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="new-password"
                        minlength="8"
                    >
                    <small>{{ __('Minimum 8 characters') }}</small>
                </div>

                <div class="aparto-form-group">
                    <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required
                        autocomplete="new-password"
                        minlength="8"
                    >
                </div>

                <div class="aparto-form-actions">
                    <button type="submit" class="aparto-btn-primary">
                        {{ __('Reset Password') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
