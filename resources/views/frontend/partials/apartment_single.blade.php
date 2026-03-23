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
        <div class="aparto-detail-price" style="margin-top: 1rem;"> {{ __('frontpage.card.price_prefix') }} {{ number_format($apartment->price_per_night, 0) }} {{ config('website.currency') }} {{ __('frontpage.card.price_suffix') }} </div>
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
                        <span class="aparto-rating-text">{{ number_format($apartment->average_rating, 1) }}
                            @if($apartment->reviews_count == 1)   
                            ({{ $apartment->reviews_count }} {{ __('frontpage.reviews.one') }})
                            @elseif($apartment->reviews_count < 5)  
                            ({{ $apartment->reviews_count }} {{ __('frontpage.reviews.less_than_five') }})
                            @else  
                            ({{ $apartment->reviews_count }} {{ __('frontpage.reviews.five_or_more') }})
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            @if(session('review_success'))
                <div class="aparto-form-message is-success aparto-review-success-message">
                    {{ session('review_success') }}
                </div>
            @endif

            @if($reviews->count() > 0)
                @php
                    $reviewsStep = 10;
                @endphp
                <div class="aparto-reviews-list" data-reviews-list data-reviews-step="{{ $reviewsStep }}">
                    @foreach($reviews as $review)
                        <div class="aparto-review-item {{ $loop->index >= $reviewsStep ? 'is-hidden' : '' }}" data-review-item>
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
                @if($reviews->count() > $reviewsStep)
                    <div class="aparto-reviews-load-more-wrap">
                        <button type="button" class="aparto-button ghost aparto-reviews-load-more" data-reviews-load-more>
                            {{ __('frontpage.reviews.show_more') }}
                        </button>
                    </div>
                @endif
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
                <div class="aparto-review-guest-cta">
                    <p class="aparto-review-notice">{{ __('frontpage.reviews.login_required') }}</p>
                    <a href="{{ $reviewLoginUrl }}" class="aparto-button primary">{{ __('frontpage.reviews.login_action') }}</a>
                </div>
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            <span data-gallery-toggle-text>{{ __('frontpage.gallery.show') }}</span>
            <span class="aparto-gallery-toggle-count">{{ count($apartment->gallery_images) }}</span>
        </button>
        <div class="aparto-gallery is-hidden" data-gallery>
            <div class="aparto-gallery-viewport" data-gallery-viewport>
                <div class="aparto-gallery-track" data-gallery-track>
                    @foreach($apartment->gallery_images as $index => $image)
                    <div class="aparto-gallery-slide">
                        <img src="{{ asset('storage/' . $image) }}" alt="{{ $apartment->title }}" loading="lazy" data-lightbox-trigger="{{ $index }}">
                    </div>
                    @endforeach
                </div>
                <button class="aparto-gallery-button prev" type="button" data-gallery-prev aria-label="Previous image">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button class="aparto-gallery-button next" type="button" data-gallery-next aria-label="Next image">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <div class="aparto-gallery-counter" data-gallery-counter></div>
            </div>
            <div class="aparto-gallery-thumbs" data-gallery-thumbs>
                @foreach($apartment->gallery_images as $index => $image)
                <button class="aparto-gallery-thumb{{ $index === 0 ? ' is-active' : '' }}" type="button" data-thumb-index="{{ $index }}">
                    <img src="{{ asset('storage/' . $image) }}" alt="" loading="lazy">
                </button>
                @endforeach
            </div>
        </div>

        <!-- Lightbox Modal -->
        <div class="aparto-lightbox" data-lightbox>
            <button class="aparto-lightbox-close" data-lightbox-close aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="aparto-lightbox-content" data-lightbox-content>
                <img src="" alt="{{ $apartment->title }}" data-lightbox-image>
            </div>
            <button class="aparto-lightbox-button prev" data-lightbox-prev aria-label="Previous image">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="aparto-lightbox-button next" data-lightbox-next aria-label="Next image">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <div class="aparto-lightbox-counter" data-lightbox-counter></div>
        </div>
        @endif

        @include('frontend.partials.host_profile', [
            'host' => $host ?? $apartment->user,
            'hostTotalReviews' => $hostTotalReviews ?? 0,
            'hostAverageRating' => $hostAverageRating ?? null,
            'hostApartmentsCount' => $hostApartmentsCount ?? 0,
        ])
    </div>

    {{-- Floating "Book Now" button (appears on scroll) --}}
    <button class="aparto-fab-book" type="button" data-reserve-toggle data-fab-book aria-label="{{ __('frontpage.detail.book') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span>{{ __('frontpage.detail.book') }}</span>
    </button>
</section>