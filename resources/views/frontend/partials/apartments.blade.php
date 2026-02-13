<section id="apartments" class="aparto-fade-up aparto-delay-1">
	<h2 class="aparto-section-title">{{ __('frontpage.featured') }}</h2>

	@if($apartments->isEmpty())
		<div class="aparto-empty">
			<h3 class="aparto-card-title">{{ __('frontpage.empty.title') }}</h3>
			<p class="aparto-hero-subtitle">{{ __('frontpage.empty.subtitle') }}</p>
		</div>
	@else
		<div class="aparto-grid">
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
						<a class="aparto-card-link" href="{{ route('apartments.show', $apartment->id) }}">{{ __('frontpage.card.details') }}</a>
					</div>
				</article>
			@endforeach
		</div>
	@endif
</section>
