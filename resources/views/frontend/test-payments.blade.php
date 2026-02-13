@extends('layouts.app')

@section('content')
    <header class="aparto-header aparto-fade-up">
        <div>
            <div class="aparto-brand">Test Payments</div>
            <nav class="aparto-nav">
                <a href="{{ route('home') }}">{{ __('frontpage.nav.home') }}</a>
                <a href="mailto:{{ config('website.contact_email') }}">{{ __('frontpage.nav.contact') }}</a>
            </nav>
        </div>
    </header>

    <section class="aparto-detail">
        <div class="aparto-detail-card">
            <h1 class="aparto-detail-title">Pending Reservations</h1>
            <p class="aparto-hero-subtitle">Use this page to simulate a successful payment.</p>

            @if(session('success'))
                <div class="aparto-form-message is-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="aparto-form-message is-error">
                    {{ session('error') }}
                </div>
            @endif

            @if($reservations->isEmpty())
                <p>No pending reservations.</p>
            @else
                <div class="aparto-results">
                    @foreach($reservations as $reservation)
                        <div class="aparto-detail-card" style="margin-bottom: 16px;">
                            <div><strong>ID:</strong> {{ $reservation->id }}</div>
                            <div><strong>Guest:</strong> {{ $reservation->name }}</div>
                            <div><strong>Dates:</strong> {{ $reservation->date_from }} - {{ $reservation->date_to }} ({{ $reservation->nights }} nights)</div>
                            <div><strong>Total:</strong> {{ config('website.currency') }} {{ number_format($reservation->total_price, 2) }}</div>
                            @if($reservation->note)
                                <div><strong>Note:</strong> {{ $reservation->note }}</div>
                            @endif
                            <form method="POST" action="{{ route('test.payments.confirm', $reservation) }}" style="margin-top: 12px;">
                                @csrf
                                <button class="aparto-button primary" type="submit">Mark as Paid</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
