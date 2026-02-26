@php
    $pages = \App\Http\Repository\PagesRepository::pages();
@endphp
<footer class="aparto-footer">
        <div class="aparto-footer-shell">
            <div>
                <a class="aparto-footer-brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
                <p class="aparto-footer-note">{{ __('frontpage.hero.footer_text') }}</p>
            </div>
            <div class="aparto-footer-links">
                <div>
                    <h4>Explore</h4>
                    <a href="{{ route('home') }}">{{ __('frontpage.nav.home') }}</a>
                    <a href="{{ route('apartments.index') }}">{{ __('frontpage.nav.apartments') }}</a>
                    <a href="{{ route('home') }}#contact">{{ __('frontpage.nav.contact') }}</a>
                </div>
                @if(!empty($pages) && $pages->isNotEmpty())
                    <div>
                        <h4>Pages</h4>
                        @foreach($pages as $page)
                            <a href="{{ route('pages.show', $page->slug) }}">{{ $page->title }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </footer>