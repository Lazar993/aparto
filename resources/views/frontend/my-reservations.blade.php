@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')

    <section class="aparto-fade-up aparto-delay-1 aparto-reservations-page">
        <h1 class="aparto-section-title">{{ __('frontpage.my_reservations.title') }}</h1>
        <p class="aparto-hero-subtitle">{{ __('frontpage.my_reservations.subtitle') }}</p>

        @if($reservations->isEmpty())
            <div class="aparto-empty aparto-reservations-empty">
                <h3 class="aparto-card-title">{{ __('frontpage.my_reservations.empty_title') }}</h3>
                <p class="aparto-hero-subtitle">{{ __('frontpage.my_reservations.empty_subtitle') }}</p>
                <a href="{{ route('apartments.index') }}" class="aparto-button primary">{{ __('frontpage.my_reservations.browse_apartments') }}</a>
            </div>
        @else
            <div class="aparto-reservations-list">
                @foreach($reservations as $reservation)
                    @php
                        $statusLabel = match ($reservation->status) {
                            'confirmed' => __('frontpage.my_reservations.status_confirmed'),
                            'canceled' => __('frontpage.my_reservations.status_canceled'),
                            default => __('frontpage.my_reservations.status_pending'),
                        };
                    @endphp

                    <article class="aparto-reservation-item">
                        @if($reservation->apartment && $reservation->apartment->lead_image)
                            <a href="{{ route('apartments.show', $reservation->apartment->id) }}" class="aparto-reservation-image">
                                <img src="{{ asset('storage/' . $reservation->apartment->lead_image) }}" alt="{{ $reservation->apartment->title }}">
                            </a>
                        @else
                            <div class="aparto-reservation-image aparto-reservation-image--empty" aria-hidden="true"></div>
                        @endif

                        <div class="aparto-reservation-item-content">
                            <div class="aparto-reservation-item-head">
                                <h3 class="aparto-card-title">
                                    {{ $reservation->apartment?->title ?? __('frontpage.my_reservations.apartment_unavailable') }}
                                </h3>
                                <span class="aparto-reservation-status is-{{ $reservation->status ?? 'pending' }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="aparto-reservation-item-meta">
                                <span><strong>{{ __('frontpage.my_reservations.check_in') }}:</strong> {{ optional($reservation->date_from)->format('d.m.Y') }}</span>
                                <span><strong>{{ __('frontpage.my_reservations.check_out') }}:</strong> {{ optional($reservation->date_to)->format('d.m.Y') }}</span>
                                <span><strong>{{ __('frontpage.my_reservations.nights') }}:</strong> {{ $reservation->nights }}</span>
                                <span><strong>{{ __('frontpage.my_reservations.total_price') }}:</strong> {{ number_format((float) $reservation->total_price, 2) }} {{ config('website.currency') }}</span>
                                <span><strong>{{ __('frontpage.my_reservations.deposit') }}:</strong> {{ number_format((float) $reservation->deposit_amount, 2) }} {{ config('website.currency') }}</span>
                            </div>

                            @if($reservation->apartment)
                                <a href="{{ route('apartments.show', $reservation->apartment->id) }}" class="aparto-button ghost aparto-reservation-link">
                                    {{ __('frontpage.my_reservations.view_apartment') }}
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="aparto-pagination">
                {{ $reservations->onEachSide(1)->links('vendor.pagination.aparto') }}
            </div>
        @endif
    </section>
@endsection
