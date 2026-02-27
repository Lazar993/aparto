@extends('layouts.app')

@section('content')
    @include('layouts.partials.header')

    <section class="aparto-auth-section">
        <div class="aparto-auth-container">
            <h1 class="aparto-auth-title">{{ __('Login') }}</h1>

            @if ($errors->any())
                <div class="aparto-auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="aparto-auth-form">
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

                <div class="aparto-form-group">
                    <label for="password">{{ __('Password') }}</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="aparto-form-group aparto-form-checkbox">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        {{ __('Remember me') }}
                    </label>
                </div>

                <div class="aparto-form-actions">
                    <button type="submit" class="aparto-btn-primary">
                        {{ __('Login') }}
                    </button>
                    <a href="{{ route('password.request') }}" class="aparto-link">
                        {{ __('Forgot your password?') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
