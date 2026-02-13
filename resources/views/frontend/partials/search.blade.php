<section class="aparto-fade-up aparto-delay-1" style="margin-bottom: 24px;">
        <form class="aparto-filter" method="GET" action="{{ route('apartments.index') }}">
            <div class="aparto-filter-row">
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="home-filter-q">{{ __('frontpage.filters.search') }}</label>
                    <input id="home-filter-q" name="q" type="text" value="{{ request('q') }}" placeholder="{{ __('frontpage.filters.search_placeholder') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="home-filter-city">{{ __('frontpage.filters.city') }}</label>
                    <select id="home-filter-city" name="city" class="aparto-filter-input">
                        <option value="">{{ __('frontpage.filters.all_cities') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="home-filter-min">{{ __('frontpage.filters.min_price') }}</label>
                    <input id="home-filter-min" name="min_price" type="number" step="1" min="0" value="{{ request('min_price') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="home-filter-max">{{ __('frontpage.filters.max_price') }}</label>
                    <input id="home-filter-max" name="max_price" type="number" step="1" min="0" value="{{ request('max_price') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="home-filter-parking">{{ __('frontpage.filters.parking') }}</label>
                    <select id="home-filter-parking" name="parking" class="aparto-filter-input">
                        <option value="">{{ __('frontpage.filters.parking_any') }}</option>
                        <option value="1" {{ request('parking') === '1' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_yes') }}</option>
                        <option value="0" {{ request('parking') === '0' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_no') }}</option>
                    </select>
                </div>
                <div class="aparto-filter-actions">
                    <button class="aparto-button primary" type="submit">{{ __('frontpage.filters.apply') }}</button>
                    <a class="aparto-button ghost" href="{{ route('apartments.index') }}">{{ __('frontpage.filters.reset') }}</a>
                </div>
            </div>
        </form>
    </section>