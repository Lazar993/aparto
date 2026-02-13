<section id="details" class="aparto-detail">
    <div class="aparto-detail-card">
        <h1 class="aparto-detail-title">{{ $apartment->title }}</h1>
        <p class="aparto-hero-subtitle">{{ __('frontpage.detail.short_intro') }}</p>
        <div class="aparto-detail-meta">
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
        <div class="aparto-detail-price" style="margin-top: 1rem;">{{ config('website.currency') }} {{ number_format($apartment->price_per_night, 0) }} {{ __('frontpage.card.price_suffix') }}</div>
        <div class="aparto-detail-actions">
            <button class="aparto-button primary" type="button" data-reserve-toggle>{{ __('frontpage.detail.book') }}</button>
            <button class="aparto-button ghost" type="button" data-description-toggle>
                {{ __('frontpage.detail.show_description') }}
            </button>
        </div>
        <div class="aparto-detail-description is-hidden" data-description>
            {{ $apartment->description }}
        </div>
        @if($apartment->latitude && $apartment->longitude)
        <div id="aparto-detail-map" class="aparto-detail-map" data-lat="{{ $apartment->latitude }}" data-lng="{{ $apartment->longitude }}"></div>
        @endif
    </div>
    <div class="aparto-detail-card">
        <div class="aparto-detail-media">
            @if($apartment->lead_image)
            <img src="{{ asset('storage/' . $apartment->lead_image) }}" alt="{{ $apartment->title }}">
            @endif
        </div>
        @if(!empty($apartment->gallery_images))
        <button class="aparto-gallery-toggle" type="button" data-gallery-toggle data-show-text="{{ __('frontpage.gallery.show') }}" data-hide-text="{{ __('frontpage.gallery.hide') }}">
            {{ __('frontpage.gallery.show') }}
        </button>
        <div class="aparto-gallery is-hidden" data-gallery>
            <div class="aparto-gallery-track" data-gallery-track>
                @foreach($apartment->gallery_images as $image)
                <div class="aparto-gallery-slide">
                    <img src="{{ asset('storage/' . $image) }}" alt="{{ $apartment->title }}">
                </div>
                @endforeach
            </div>
            <button class="aparto-gallery-button prev" type="button" data-gallery-prev aria-label="Previous image">&#8249;</button>
            <button class="aparto-gallery-button next" type="button" data-gallery-next aria-label="Next image">&#8250;</button>
            <div class="aparto-gallery-dots" data-gallery-dots></div>
        </div>
        @endif
    </div>
</section>