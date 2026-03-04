@if($apartments->isEmpty())
    <div class="aparto-empty">
        <h3 class="aparto-card-title">{{ __('frontpage.empty.title') }}</h3>
        <p class="aparto-hero-subtitle">{{ __('frontpage.empty.subtitle') }}</p>
    </div>
@else
    <div class="aparto-grid" style="margin-top: 24px;">
        @foreach($apartments as $apartment)
            <article class="aparto-card">
                <a class="aparto-card-media" href="{{ route('apartments.show', $apartment->id) }}">
                    @if($apartment->lead_image)
                        <img src="{{ asset('storage/' . $apartment->lead_image) }}" alt="{{ $apartment->title }}">
                    @endif
                </a>
                <div class="aparto-card-body">
                    <h3 class="aparto-card-title">
                        <a class="aparto-card-title-link" href="{{ route('apartments.show', $apartment->id) }}">
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
                    <div class="aparto-price">{{ config('website.currency') }} {{ number_format($apartment->price_per_night, 0) }} {{ __('frontpage.card.price_suffix') }}</div>
                    <div class="aparto-card-footer">
                        {{-- <a class="aparto-card-link" href="{{ route('apartments.show', $apartment->id) }}">{{ __('frontpage.card.details') }}</a> --}}
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
                        @else
                            <span class="aparto-card-rating-summary aparto-card-rating-empty">{{ __('frontpage.reviews.no_reviews') }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="aparto-pagination">
        {{ $apartments->links() }}
    </div>
@endif
