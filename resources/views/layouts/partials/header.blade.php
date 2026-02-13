<header class="aparto-header aparto-fade-up">
    <div>
        <div class="aparto-brand">
            <a href="{{ route('home') }}">{{ config('app.name') }}</a>
        </div>
        <nav class="aparto-nav">
            <a href="{{ url('/') }}">{{ __('frontpage.nav.home') }}</a>
            <a href="{{ route('apartments.index') }}">{{ __('frontpage.nav.apartments') }}</a>
            <a href="#contact">{{ __('frontpage.nav.contact') }}</a>
        </nav>
    </div>
    <div class="aparto-lang">
        <a href="{{ route('locale.switch', 'sr') }}" class="{{ app()->getLocale() === 'sr' ? 'is-active' : '' }}">SR</a>
        <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
        <a href="{{ route('locale.switch', 'ru') }}" class="{{ app()->getLocale() === 'ru' ? 'is-active' : '' }}">RU</a>
    </div>
</header>