<header class="aparto-header aparto-fade-up">
    @php
        $switchUrl = static function (string $locale): string {
            return route('locale.switch', ['locale' => $locale]);
        };

        $currentRoute = request()->route()?->getName();
        $currentUrl = url()->current();
    @endphp
    <div class="aparto-header-left">
        <div class="aparto-brand">
            <a href="{{ route('home') }}">{{ config('app.name') }}</a>
        </div>
        <nav class="aparto-nav aparto-nav--desktop">
            <a href="{{ route('home') }}" class="{{ $currentRoute === 'home' ? 'is-active' : '' }}">{{ __('frontpage.nav.home') }}</a>
            <a href="{{ route('apartments.index') }}" class="{{ $currentRoute === 'apartments.index' ? 'is-active' : '' }}">{{ __('frontpage.nav.apartments') }}</a>
            <a href="{{ route('contact.show') }}" class="{{ $currentRoute === 'contact.show' ? 'is-active' : '' }}">{{ __('frontpage.nav.contact') }}</a>
            <a href="{{ route('become-host.show') }}" class="{{ $currentRoute === 'become-host.show' ? 'is-active' : '' }}">{{ __('frontpage.nav.become_host') }}</a>
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
                        <span class="aparto-auth-label">{{ __('frontpage.nav.logout') }}</span>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="aparto-nav-link aparto-auth-link aparto-auth-link--login" style="font-weight: 600;" aria-label="{{ __('Login') }}">
                <img src="{{ asset('images/icons/login.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--login" aria-hidden="true">
                <span class="aparto-auth-label">{{ __('frontpage.nav.login') }}</span>
            </a>
        @endauth
        <div class="aparto-lang">
            <a href="{{ $switchUrl('sr') }}" class="{{ app()->getLocale() === 'sr' ? 'is-active' : '' }}">SR</a>
            <a href="{{ $switchUrl('en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
            <a href="{{ $switchUrl('ru') }}" class="{{ app()->getLocale() === 'ru' ? 'is-active' : '' }}">RU</a>
        </div>

        {{-- Hamburger button (mobile only) --}}
        <button
            class="aparto-hamburger"
            id="aparto-hamburger-btn"
            type="button"
            aria-label="Menu"
            aria-expanded="false"
        >
            <span class="aparto-hamburger-line"></span>
            <span class="aparto-hamburger-line"></span>
            <span class="aparto-hamburger-line"></span>
        </button>
    </div>

    {{-- Mobile drawer --}}
    <div
        class="aparto-mobile-menu"
        id="aparto-mobile-menu"
        style="display:none; position:absolute; top:100%; right:0; z-index:9999; width:280px; background:#ffffff; border:1px solid rgba(32,76,69,0.12); border-radius:16px; padding:20px; box-shadow:0 20px 50px rgba(34,24,18,0.16);"
    >
        <nav class="aparto-mobile-nav">
            <a href="{{ route('home') }}" class="{{ $currentRoute === 'home' ? 'is-active' : '' }}">{{ __('frontpage.nav.home') }}</a>
            <a href="{{ route('apartments.index') }}" class="{{ $currentRoute === 'apartments.index' ? 'is-active' : '' }}">{{ __('frontpage.nav.apartments') }}</a>
            <a href="{{ route('contact.show') }}" class="{{ $currentRoute === 'contact.show' ? 'is-active' : '' }}">{{ __('frontpage.nav.contact') }}</a>
            <a href="{{ route('become-host.show') }}" class="{{ $currentRoute === 'become-host.show' ? 'is-active' : '' }}">{{ __('frontpage.nav.become_host') }}</a>
        </nav>

        <div class="aparto-mobile-divider"></div>

        <div class="aparto-mobile-actions">
            @auth
                <a href="{{ route('reservations.mine') }}" class="aparto-mobile-action-link">
                    <img src="{{ asset('images/icons/profile.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--profile" aria-hidden="true">
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="aparto-mobile-action-link aparto-mobile-action-link--logout">
                        <img src="{{ asset('images/icons/logout.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--logout" aria-hidden="true">
                        {{ __('frontpage.nav.logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="aparto-mobile-action-link">
                    <img src="{{ asset('images/icons/login.svg') }}" alt="" class="aparto-auth-icon aparto-auth-icon--login" aria-hidden="true">
                    {{ __('frontpage.nav.login') }}
                </a>
            @endauth
        </div>

        <div class="aparto-mobile-divider"></div>

        <div class="aparto-mobile-lang">
            <a href="{{ $switchUrl('sr') }}" class="{{ app()->getLocale() === 'sr' ? 'is-active' : '' }}">SR</a>
            <a href="{{ $switchUrl('en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
            <a href="{{ $switchUrl('ru') }}" class="{{ app()->getLocale() === 'ru' ? 'is-active' : '' }}">RU</a>
        </div>
    </div>
</header>

<script>
(function() {
    var btn = document.getElementById('aparto-hamburger-btn');
    var menu = document.getElementById('aparto-mobile-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';
        btn.setAttribute('aria-expanded', !isOpen);
        // Toggle hamburger animation
        var lines = btn.querySelectorAll('.aparto-hamburger-line');
        lines.forEach(function(line) {
            line.classList.toggle('is-open', !isOpen);
        });
    });

    menu.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function() {
        menu.style.display = 'none';
        btn.setAttribute('aria-expanded', 'false');
        btn.querySelectorAll('.aparto-hamburger-line').forEach(function(line) {
            line.classList.remove('is-open');
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            menu.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            btn.querySelectorAll('.aparto-hamburger-line').forEach(function(line) {
                line.classList.remove('is-open');
            });
        }
    });
})();
</script>