<section class="aparto-fade-up aparto-delay-1" style="margin-bottom: 24px;">
    @php
        $hasAdvancedFilters = request()->filled('q')
            || request()->filled('min_price')
            || request()->filled('max_price')
            || request()->filled('parking');
    @endphp

    <form id="home-aparto-filter" class="aparto-filter aparto-filter--dense aparto-filter--premium" method="GET" action="{{ route('apartments.index') }}">
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
                <div class="aparto-filter-field aparto-filter-field--guests">
                    <label class="aparto-filter-label" for="home-filter-guests">{{ __('frontpage.filters.guests') }}</label>
                    <input id="home-filter-guests" name="guests" type="number" step="1" min="1" value="{{ request('guests') }}" class="aparto-filter-input">
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
                    <button class="aparto-button primary" type="submit">{{ __('frontpage.filters.apply') }}</button>
                    <a class="aparto-button ghost" href="{{ route('home') }}" data-filter-reset>{{ __('frontpage.filters.reset') }}</a>
                </div>
            </div>

            <details class="aparto-filter-advanced" {{ $hasAdvancedFilters ? 'open' : '' }}>
                <summary class="aparto-filter-advanced-toggle">
                    <span>{{ __('frontpage.filters.more_filters') }}</span>
                </summary>
                <div class="aparto-filter-advanced-body">
                    <div class="aparto-filter-row aparto-filter-row--advanced">
                        <div class="aparto-filter-field aparto-filter-field--search">
                            <label class="aparto-filter-label" for="home-filter-q">{{ __('frontpage.filters.search') }}</label>
                            <input id="home-filter-q" name="q" type="text" value="{{ request('q') }}" placeholder="{{ __('frontpage.filters.search_placeholder') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--price">
                            <label class="aparto-filter-label" for="home-filter-min">{{ __('frontpage.filters.min_price') }}</label>
                            <input id="home-filter-min" name="min_price" type="number" step="1" min="0" value="{{ request('min_price') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--price">
                            <label class="aparto-filter-label" for="home-filter-max">{{ __('frontpage.filters.max_price') }}</label>
                            <input id="home-filter-max" name="max_price" type="number" step="1" min="0" value="{{ request('max_price') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--parking">
                            <label class="aparto-filter-label" for="home-filter-parking">{{ __('frontpage.filters.parking') }}</label>
                            <select id="home-filter-parking" name="parking" class="aparto-filter-input">
                                <option value="">{{ __('frontpage.filters.parking_any') }}</option>
                                <option value="1" {{ request('parking') === '1' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_yes') }}</option>
                                <option value="0" {{ request('parking') === '0' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_no') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </details>
        </form>
    </section>