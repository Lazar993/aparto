<section class="aparto-hero">
    <div class="aparto-fade-up aparto-delay-1">
        <h1 class="aparto-hero-title">{{ __('frontpage.hero.title') }}</h1>
        <p class="aparto-hero-subtitle">
            {{ __('frontpage.hero.subtitle') }}
        </p>
        {{-- <div class="aparto-hero-actions">
            <a href="{{ route('apartments.index') }}" class="aparto-button primary">{{ __('frontpage.hero.cta_primary') }}</a>
            <a href="#contact" class="aparto-button ghost">{{ __('frontpage.hero.cta_secondary') }}</a>
        </div> --}}
    </div>
    <div class="aparto-hero-card aparto-fade-up aparto-delay-2">
        <div class="aparto-hero-stat">
            <strong>{{ (int) ($homepageStats->available_count ?? 0) }}</strong>
            <span>{{ __('frontpage.stats.available') }}</span>
        </div>
        <div class="aparto-hero-stat">
            <strong>{{ config('website.currency') }} {{ number_format((float) ($homepageStats->min_price ?? 0), 0) }}</strong>
            <span>{{ __('frontpage.stats.starting') }}</span>
        </div>
        <div class="aparto-hero-stat">
            <strong>Parking</strong>
            <span>{{ (int) ($homepageStats->parking_count ?? 0) }} {{ __('frontpage.stats.parking') }}</span>
        </div>
    </div>
</section>