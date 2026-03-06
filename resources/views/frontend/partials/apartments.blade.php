@php
	$homepageSections = [
		[
			'title' => __('frontpage.homepage_sections.popular'),
			'icon' => asset('images/icons/fire.svg'),
			'icon_alt' => 'Popular',
			'link_label' => __('frontpage.homepage_sections.links.popular'),
			'link_url' => route('apartments.popular'),
			'items' => $popularApartments,
		],
		[
			'title' => __('frontpage.homepage_sections.best_rated'),
			'icon' => asset('images/icons/star.svg'),
			'icon_alt' => 'Best rated',
			'link_label' => __('frontpage.homepage_sections.links.reviewed'),
			'link_url' => route('apartments.reviewed'),
			'items' => $bestRatedApartments,
		],
		[
			'title' => __('frontpage.homepage_sections.newest'),
			'icon' => asset('images/icons/latest.svg'),
			'icon_alt' => 'Newest',
			'link_label' => __('frontpage.homepage_sections.links.newest'),
			'link_url' => route('apartments.index'),
			'items' => $newestApartments,
		],
	];
@endphp

<section id="apartments" class="aparto-fade-up aparto-delay-1 aparto-home-sections">
	@foreach($homepageSections as $section)
		<div class="aparto-home-section">
			<div class="aparto-home-section-heading">
				<h2 class="aparto-section-title aparto-section-title--with-icon">
					<img src="{{ $section['icon'] }}" alt="{{ $section['icon_alt'] }}" class="aparto-section-title-icon">
					<span>{{ $section['title'] }}</span>
				</h2>
				<a class="aparto-section-link" href="{{ $section['link_url'] }}">{{ $section['link_label'] }}</a>
			</div>

			@if($section['items']->isEmpty())
				<div class="aparto-empty">
					<h3 class="aparto-card-title">{{ __('frontpage.empty.title') }}</h3>
					<p class="aparto-hero-subtitle">{{ __('frontpage.homepage_sections.empty') }}</p>
				</div>
			@else
				<div class="aparto-home-scroll-wrap">
					<div class="aparto-grid aparto-grid--homepage">
						@foreach($section['items'] as $apartment)
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
									<div class="aparto-price"> {{ __('frontpage.card.price_prefix') }} {{ number_format($apartment->price_per_night, 0) }} {{ config('website.currency') }} {{ __('frontpage.card.price_suffix') }} </div>
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
										@else
											<span class="aparto-card-rating-summary aparto-card-rating-empty">{{ __('frontpage.reviews.no_reviews') }}</span>
										@endif
									</div>
								</div>
							</article>
						@endforeach
					</div>
				</div>
			@endif
		</div>
	@endforeach
</section>
