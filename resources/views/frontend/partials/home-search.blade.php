<section class="aparto-fade-up aparto-delay-1" style="margin-bottom: 24px;">
    @php
        $filterIconVars = "--aparto-icon-city: url('" . asset('images/icons/city.svg') . "');"
            . " --aparto-icon-calendar: url('" . asset('images/icons/calendar.svg') . "');"
            . " --aparto-icon-search: url('" . asset('images/icons/search.svg') . "');";
    @endphp

    <form id="home-aparto-filter" class="aparto-filter aparto-filter--dense aparto-filter--premium aparto-filter--home-simple" method="GET" action="{{ route('apartments.index') }}" style="{{ $filterIconVars }}">
        <div class="aparto-filter-primary">
            <div class="aparto-filter-field aparto-filter-field--city">
                <label class="aparto-filter-label" for="home-filter-city">{{ __('frontpage.filters.city') }}</label>
                <input id="home-filter-city" name="city" type="text" list="home-filter-city-options" value="{{ request('city') }}" placeholder="{{ __('frontpage.filters.all_cities') }}" class="aparto-filter-input" autocomplete="off">
                <datalist id="home-filter-city-options">
                    @foreach($cities as $city)
                        <option value="{{ $city }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="aparto-filter-field aparto-filter-field--checkin">
                <label class="aparto-filter-label" for="home-filter-date-from">{{ __('frontpage.filters.check_in') }}</label>
                <input id="home-filter-date-from" name="date_from" type="date" value="{{ request('date_from') }}" min="{{ now()->toDateString() }}" class="aparto-filter-input">
            </div>
            <div class="aparto-filter-field aparto-filter-field--checkout">
                <label class="aparto-filter-label" for="home-filter-date-to">{{ __('frontpage.filters.check_out') }}</label>
                <input id="home-filter-date-to" name="date_to" type="date" value="{{ request('date_to') }}" min="{{ request('date_from') ?: now()->toDateString() }}" class="aparto-filter-input">
            </div>
            <div class="aparto-filter-actions">
                <button class="aparto-button primary" type="submit">{{ __('frontpage.filters.search') }}</button>
            </div>
        </div>
    </form>
</section>
