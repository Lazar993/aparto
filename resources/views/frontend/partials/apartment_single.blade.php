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
            <button class="aparto-button ghost" type="button" data-reviews-toggle>
                {{ __('frontpage.reviews.show') }}
            </button>
        </div>
        <div class="aparto-detail-description is-hidden" data-description>
            {{ $apartment->description }}
        </div>
        <div class="aparto-detail-reviews is-hidden" data-reviews>
            <div class="aparto-reviews-header">
                <h3 class="aparto-reviews-title">{{ __('frontpage.reviews.title') }}</h3>
                @if($apartment->reviews_count > 0)
                    <div class="aparto-reviews-summary">
                        <div class="aparto-rating-display">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="aparto-star {{ $i <= round($apartment->average_rating) ? 'filled' : '' }}">★</span>
                            @endfor
                        </div>
                        <span class="aparto-rating-text">{{ number_format($apartment->average_rating, 1) }} ({{ $apartment->reviews_count }} {{ $apartment->reviews_count == 1 ? 'review' : 'reviews' }})</span>
                    </div>
                @endif
            </div>

            @if($reviews->count() > 0)
                <div class="aparto-reviews-list">
                    @foreach($reviews as $review)
                        <div class="aparto-review-item">
                            <div class="aparto-review-header">
                                <div class="aparto-review-author">
                                    <strong>{{ $review->user->name }}</strong>
                                    <span class="aparto-review-date">{{ $review->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="aparto-rating-display">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="aparto-star {{ $i <= $review->rating ? 'filled' : '' }}">★</span>
                                    @endfor
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="aparto-review-comment">{{ $review->comment }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="aparto-reviews-empty">
                    <p>{{ __('frontpage.reviews.no_reviews') }}</p>
                    <small>{{ __('frontpage.reviews.no_reviews_desc') }}</small>
                </div>
            @endif

            @auth
                @if($userCanReview)
                    <div class="aparto-review-form">
                        <h4>{{ __('frontpage.reviews.write_review') }}</h4>
                        <form action="{{ route('reviews.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="apartment_id" value="{{ $apartment->id }}">
                            
                            <div class="aparto-form-group">
                                <label>{{ __('frontpage.reviews.your_rating') }}</label>
                                <div class="aparto-rating-input" data-rating-input>
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="aparto-star-input" data-rating="{{ $i }}">★</span>
                                    @endfor
                                    <input type="hidden" name="rating" id="rating-value" required>
                                </div>
                                @error('rating')
                                    <span class="aparto-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="aparto-form-group">
                                <label for="comment">{{ __('frontpage.reviews.your_comment') }}</label>
                                <textarea name="comment" id="comment" rows="4" placeholder="{{ __('frontpage.reviews.comment_placeholder') }}">{{ old('comment') }}</textarea>
                                @error('comment')
                                    <span class="aparto-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="aparto-button primary">{{ __('frontpage.reviews.submit') }}</button>
                        </form>
                    </div>
                @elseif($userHasReviewed)
                    <p class="aparto-review-notice">{{ __('frontpage.reviews.already_reviewed') }}</p>
                @else
                    <p class="aparto-review-notice">{{ __('frontpage.reviews.reservation_required') }}</p>
                @endif
            @else
                <p class="aparto-review-notice">{{ __('frontpage.reviews.login_required') }}</p>
            @endauth
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
                @foreach($apartment->gallery_images as $index => $image)
                <div class="aparto-gallery-slide">
                    <img src="{{ asset('storage/' . $image) }}" alt="{{ $apartment->title }}" data-lightbox-trigger="{{ $index }}" style="cursor: pointer;">
                </div>
                @endforeach
            </div>
            <button class="aparto-gallery-button prev" type="button" data-gallery-prev aria-label="Previous image">&#8249;</button>
            <button class="aparto-gallery-button next" type="button" data-gallery-next aria-label="Next image">&#8250;</button>
            <div class="aparto-gallery-dots" data-gallery-dots></div>
        </div>
        
        <!-- Lightbox Modal -->
        <div class="aparto-lightbox" data-lightbox>
            <button class="aparto-lightbox-close" data-lightbox-close aria-label="Close">&#10005;</button>
            <div class="aparto-lightbox-content">
                <img src="" alt="{{ $apartment->title }}" data-lightbox-image>
            </div>
            <button class="aparto-lightbox-button prev" data-lightbox-prev aria-label="Previous image">&#8249;</button>
            <button class="aparto-lightbox-button next" data-lightbox-next aria-label="Next image">&#8250;</button>
            <div class="aparto-lightbox-counter" data-lightbox-counter></div>
        </div>
        @endif
    </div>
</section>