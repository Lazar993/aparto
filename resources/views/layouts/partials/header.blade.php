<header class="aparto-header aparto-fade-up">
    @php
        $currentPageSlug = request()->route('slug');
        $isLocalizedPage = request()->routeIs('pages.show') && is_string($currentPageSlug) && $currentPageSlug !== '';
        $switchUrl = static function (string $locale) use ($isLocalizedPage, $currentPageSlug): string {
            if ($isLocalizedPage) {
                return route('pages.show', ['locale' => $locale, 'slug' => $currentPageSlug]);
            }

            return route('locale.switch', $locale);
        };
    @endphp
    <div>
        <div class="aparto-brand">
            <a href="{{ route('home') }}">{{ config('app.name') }}</a>
        </div>
        <nav class="aparto-nav">
            <a href="{{ url('/') }}">{{ __('frontpage.nav.home') }}</a>
            <a href="{{ route('apartments.index') }}">{{ __('frontpage.nav.apartments') }}</a>
            <a href="{{ route('contact.show') }}">{{ __('frontpage.nav.contact') }}</a>
        </nav>
    </div>
    <div class="aparto-header-actions">
        @auth
            <div class="aparto-user-menu">
                <a href="{{ route('reservations.mine') }}" class="aparto-user-email aparto-user-link aparto-auth-link aparto-auth-link--profile" aria-label="{{ __('frontpage.my_reservations.title') }}">
                    <img src="{{ asset('images/icons/profile.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--profile" aria-hidden="true">
                    <span class="aparto-auth-label">{{ auth()->user()->name }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="aparto-logout-btn aparto-auth-link aparto-auth-link--logout" aria-label="{{ __('Logout') }}">
                        <img src="{{ asset('images/icons/logout.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--logout" aria-hidden="true">
                        <span class="aparto-auth-label">{{ __('Logout') }}</span>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="aparto-nav-link aparto-auth-link aparto-auth-link--login" style="font-weight: 600;" aria-label="{{ __('Login') }}">
                <img src="{{ asset('images/icons/login.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--login" aria-hidden="true">
                <span class="aparto-auth-label">{{ __('Login') }}</span>
            </a>
        @endauth
        <div class="aparto-lang">
            <a href="{{ $switchUrl('sr') }}" class="{{ app()->getLocale() === 'sr' ? 'is-active' : '' }}">SR</a>
            <a href="{{ $switchUrl('en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
            <a href="{{ $switchUrl('ru') }}" class="{{ app()->getLocale() === 'ru' ? 'is-active' : '' }}">RU</a>
        </div>
    </div>
</header>