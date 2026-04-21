@extends('layouts.app')

@section('seo_title', __('frontpage.seo.host_profile.title', ['name' => $host->name]))
@section('seo_description', __('frontpage.seo.host_profile.description', ['name' => $host->name]))
@section('seo_keywords', __('frontpage.seo.host_profile.keywords', ['name' => $host->name]))
@if($host->profile_image)
@section('seo_image', asset('storage/' . $host->profile_image))
@endif

@section('content')

    @include('layouts.partials.header')

    <section class="aparto-fade-up aparto-delay-1">
        <h1 class="aparto-section-title">{{ __('frontpage.host.page_title') }}</h1>

        <div class="aparto-host-page-header">
            <div class="aparto-host-page-avatar">
                @if($host->profile_image)
                    <img src="{{ asset('storage/' . $host->profile_image) }}" alt="{{ $host->name }}">
                @else
                    <img src="{{ asset('images/default-profile-image.svg') }}" alt="{{ $host->name }}">
                @endif
            </div>
            <div class="aparto-host-page-info">
                <span class="aparto-host-page-name">{{ $host->name }}</span>
                {{-- <span class="aparto-host-page-email">{{ $host->email }}</span> --}}
                @if($hostTotalReviews > 0)
                    <div class="aparto-host-page-stats">
                        <span class="aparto-host-stat">
                            <span class="aparto-star filled">★</span>
                            <strong>{{ $hostAverageRating }}</strong>
                            {{ __('frontpage.host.rating') }}
                        </span>
                        <span class="aparto-host-stat-divider">·</span>
                        <span class="aparto-host-stat">
                            <strong>{{ $hostTotalReviews }}</strong>
                            @if($hostTotalReviews == 1)
                                {{ __('frontpage.host.reviews_one') }}
                            @elseif($hostTotalReviews < 5)
                                {{ __('frontpage.host.reviews_few') }}
                            @else
                                {{ __('frontpage.host.reviews_many') }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <h2 class="aparto-host-apartments-title">{{ __('frontpage.host.apartments_title', ['name' => $host->name]) }}</h2>

        @if($apartments->isEmpty())
            <div class="aparto-empty">
                <p class="aparto-hero-subtitle">{{ __('frontpage.host.no_apartments') }}</p>
            </div>
        @else
            <div class="aparto-grid">
                @foreach($apartments as $apartment)
                    <article class="aparto-card">
                        <div class="aparto-card-media-wrap">
                            <a class="aparto-card-media" href="{{ route('apartments.show', ['id' => $apartment->id, 'slug' => $apartment->slug]) }}">
                                @if($apartment->lead_image)
                                    <img src="{{ asset('storage/' . $apartment->lead_image) }}" alt="{{ $apartment->title }}">
                                @endif
                            </a>
                        </div>
                        <div class="aparto-card-body">
                            <h3 class="aparto-card-title">
                                <a class="aparto-card-title-link" href="{{ route('apartments.show', ['id' => $apartment->id, 'slug' => $apartment->slug]) }}">
                                    {{ $apartment->title }}
                                </a>
                            </h3>
                            <div class="aparto-card-meta">
                                <span class="aparto-meta-item aparto-meta-pill">
                                    <img src="{{ asset('images/icons/city.svg') }}" alt="City" class="aparto-meta-icon">
                                    <span>{{ $apartment->city }}</span>
                                </span>
                                <span class="aparto-meta-item aparto-meta-pill">
                                    <img src="{{ asset('images/icons/rooms.svg') }}" alt="Rooms" class="aparto-meta-icon">
                                    <span>{{ __('frontpage.card.rooms_label') }}: {{ $apartment->rooms }}</span>
                                </span>
                                @if((int) $apartment->guest_number > 0)
                                    <span class="aparto-meta-item aparto-meta-pill">
                                        <img src="{{ asset('images/icons/guests.svg') }}" alt="Guests" class="aparto-meta-icon">
                                        <span>{{ __('frontpage.card.guests_label') }}: {{ $apartment->guest_number }}</span>
                                    </span>
                                @endif
                                @if($apartment->parking)
                                    <span class="aparto-meta-item aparto-meta-pill">
                                        <img src="{{ asset('images/icons/parking.svg') }}" alt="Parking" class="aparto-meta-icon">
                                        <span>{{ __('frontpage.card.parking') }}</span>
                                    </span>
                                @endif
                                @if($apartment->wifi)
                                    <span class="aparto-meta-item aparto-meta-pill">
                                        <img src="{{ asset('images/icons/wifi.svg') }}" alt="Wifi" class="aparto-meta-icon">
                                        <span>{{ __('frontpage.card.wifi') }}</span>
                                    </span>
                                @endif
                                @if($apartment->pet_friendly)
                                    <span class="aparto-meta-item aparto-meta-pill">
                                        <img src="{{ asset('images/icons/pet.svg') }}" alt="Pet friendly" class="aparto-meta-icon">
                                        <span>{{ __('frontpage.card.pet_friendly') }}</span>
                                    </span>
                                @endif
                            </div>
                            <div class="aparto-price">{{ __('frontpage.card.price_prefix') }} {{ number_format($apartment->price_per_night, 0) }} {{ config('website.currency') }} {{ __('frontpage.card.price_suffix') }}</div>
                            <div class="aparto-card-footer">
                                @if((int) $apartment->reviews_count > 0)
                                    <span class="aparto-card-rating-summary" title="{{ __('frontpage.reviews.average_rating') }}">
                                        <span class="aparto-card-rating-star">★</span>
                                        <span>{{ number_format((float) $apartment->average_rating, 1) }}</span>
                                        <span class="aparto-card-rating-separator">·</span>
                                        <span>
                                            {{ (int) $apartment->reviews_count }}
                                            @if($apartment->reviews_count == 1)
                                                {{ __('frontpage.reviews.one') }}
                                            @elseif($apartment->reviews_count < 5)
                                                {{ __('frontpage.reviews.less_than_five') }}
                                            @else
                                                {{ __('frontpage.reviews.five_or_more') }}
                                            @endif
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

@endsection
