<section class="aparto-hero">
    <div class="aparto-fade-up aparto-delay-1">
        <h1 class="aparto-hero-title">{{ __('frontpage.hero.title') }}</h1>
        <p class="aparto-hero-subtitle">
            {{ __('frontpage.hero.subtitle') }}
        </p>
        <div class="aparto-hero-actions">
            <a href="{{ route('apartments.index') }}" class="aparto-button primary">{{ __('frontpage.hero.cta_primary') }}</a>
            <a href="#contact" class="aparto-button ghost">{{ __('frontpage.hero.cta_secondary') }}</a>
        </div>
    </div>
    <div class="aparto-hero-card aparto-fade-up aparto-delay-2">
        <div class="aparto-hero-stat">
            <strong>{{ $apartments->count() }}</strong>
            <span>{{ __('frontpage.stats.available') }}</span>
        </div>
        <div class="aparto-hero-stat">
            <strong>{{ config('website.currency') }} {{ number_format($apartments->min('price_per_night') ?? 0, 0) }}</strong>
            <span>{{ __('frontpage.stats.starting') }}</span>
        </div>
        <div class="aparto-hero-stat">
            <strong>Parking</strong>
            <span>{{ $apartments->where('parking', true)->count() }} {{ __('frontpage.stats.parking') }}</span>
        </div>
    </div>
</section>