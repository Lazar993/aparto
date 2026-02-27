@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-auth-section">
        <div class="aparto-auth-container">
            <h1 class="aparto-auth-title">{{ __('Reset Password') }}</h1>
            <p class="aparto-auth-description">
                {{ __('Enter your email address and we will send you a link to reset your password.') }}
            </p>

            @if (session('status'))
                <div class="aparto-auth-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="aparto-auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="aparto-auth-form">
                @csrf

                <div class="aparto-form-group">
                    <label for="email">{{ __('Email') }}</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                        autocomplete="email"
                    >
                </div>

                <div class="aparto-form-actions">
                    <button type="submit" class="aparto-btn-primary">
                        {{ __('Send Reset Link') }}
                    </button>
                    <a href="{{ route('login') }}" class="aparto-link">
                        {{ __('Back to login') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
