@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')

    <section class="aparto-fade-up aparto-delay-1">
        <h1 class="aparto-section-title">{{ __('frontpage.apartments.title') }}</h1>
        <p class="aparto-hero-subtitle">{{ __('frontpage.apartments.subtitle') }}</p>

        @php
            $hasAdvancedFilters = request()->filled('q')
                || request()->filled('min_price')
                || request()->filled('max_price')
                || request()->filled('parking');
        @endphp

        <form class="aparto-filter aparto-filter--dense aparto-filter--premium aparto-filter--sticky" method="GET" action="{{ route('apartments.index') }}">
            <div class="aparto-filter-primary">
                <div class="aparto-filter-field aparto-filter-field--city">
                    <label class="aparto-filter-label" for="filter-city">{{ __('frontpage.filters.city') }}</label>
                    <input id="filter-city" name="city" type="text" list="filter-city-options" value="{{ request('city') }}" placeholder="{{ __('frontpage.filters.all_cities') }}" class="aparto-filter-input" autocomplete="off">
                    <datalist id="filter-city-options">
                        @foreach($cities as $city)
                            <option value="{{ $city }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="aparto-filter-field aparto-filter-field--guests">
                    <label class="aparto-filter-label" for="filter-guests">{{ __('frontpage.filters.guests') }}</label>
                    <input id="filter-guests" name="guests" type="number" step="1" min="1" value="{{ request('guests') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field aparto-filter-field--checkin">
                    <label class="aparto-filter-label" for="filter-date-from">{{ __('frontpage.filters.check_in') }}</label>
                    <input id="filter-date-from" name="date_from" type="date" value="{{ request('date_from') }}" min="{{ now()->toDateString() }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field aparto-filter-field--checkout">
                    <label class="aparto-filter-label" for="filter-date-to">{{ __('frontpage.filters.check_out') }}</label>
                    <input id="filter-date-to" name="date_to" type="date" value="{{ request('date_to') }}" min="{{ request('date_from') ?: now()->toDateString() }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-actions">
                    <button class="aparto-button primary" type="submit">{{ __('frontpage.filters.apply') }}</button>
                    <a class="aparto-button ghost" href="{{ route('apartments.index') }}" data-filter-reset>{{ __('frontpage.filters.reset') }}</a>
                </div>
            </div>

            <details class="aparto-filter-advanced" {{ $hasAdvancedFilters ? 'open' : '' }}>
                <summary class="aparto-filter-advanced-toggle">
                    <span>{{ __('frontpage.filters.more_filters') }}</span>
                </summary>
                <div class="aparto-filter-advanced-body">
                    <div class="aparto-filter-row aparto-filter-row--advanced">
                        <div class="aparto-filter-field aparto-filter-field--search">
                            <label class="aparto-filter-label" for="filter-q">{{ __('frontpage.filters.search') }}</label>
                            <input id="filter-q" name="q" type="text" value="{{ request('q') }}" placeholder="{{ __('frontpage.filters.search_placeholder') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--price">
                            <label class="aparto-filter-label" for="filter-min">{{ __('frontpage.filters.min_price') }}</label>
                            <input id="filter-min" name="min_price" type="number" step="1" min="0" value="{{ request('min_price') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--price">
                            <label class="aparto-filter-label" for="filter-max">{{ __('frontpage.filters.max_price') }}</label>
                            <input id="filter-max" name="max_price" type="number" step="1" min="0" value="{{ request('max_price') }}" class="aparto-filter-input">
                        </div>
                        <div class="aparto-filter-field aparto-filter-field--parking">
                            <label class="aparto-filter-label" for="filter-parking">{{ __('frontpage.filters.parking') }}</label>
                            <select id="filter-parking" name="parking" class="aparto-filter-input">
                                <option value="">{{ __('frontpage.filters.parking_any') }}</option>
                                <option value="1" {{ request('parking') === '1' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_yes') }}</option>
                                <option value="0" {{ request('parking') === '0' ? 'selected' : '' }}>{{ __('frontpage.filters.parking_no') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </details>
        </form>

        <div id="aparto-results" class="aparto-results aparto-results--list">
            @include('frontend.partials.apartments-results', ['apartments' => $apartments])
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var $form = $('.aparto-filter');
            var $results = $('#aparto-results');
            var debounceTimer = null;

            function loadResults(url) {
                $results.addClass('is-loading');
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: $form.serialize(),
                    dataType: 'json',
                }).done(function (data) {
                    if (data && data.html !== undefined) {
                        $results.html(data.html);
                        $results.removeClass('is-loading');
                        $results.addClass('is-highlight');
                        setTimeout(function () {
                            $results.removeClass('is-highlight');
                        }, 600);
                    }
                }).always(function () {
                    $results.removeClass('is-loading');
                });
            }

            function syncDateBounds() {
                var $checkIn = $form.find('input[name="date_from"]');
                var $checkOut = $form.find('input[name="date_to"]');
                var checkInValue = $checkIn.val();

                if (!checkInValue) {
                    $checkOut.attr('min', '{{ now()->toDateString() }}');
                    return;
                }

                $checkOut.attr('min', checkInValue);

                if ($checkOut.val() && $checkOut.val() <= checkInValue) {
                    $checkOut.val('');
                }
            }

            syncDateBounds();

            $form.on('submit', function (event) {
                event.preventDefault();
                loadResults($form.attr('action'));
            });

            $form.find('[data-filter-reset]').on('click', function (event) {
                event.preventDefault();

                $form.find('input[type="text"], input[type="number"], input[type="date"]').val('');

                $form.find('select').each(function () {
                    var $select = $(this);
                    if ($select.find('option[value=""]').length) {
                        $select.val('');
                    } else {
                        $select.prop('selectedIndex', 0);
                    }
                });

                $form.find('.aparto-filter-advanced').prop('open', false);

                loadResults($form.attr('action'));
            });

            $form.find('select, input[type="number"], input[type="date"]').on('change', function () {
                syncDateBounds();
                loadResults($form.attr('action'));
            });

            $form.find('input[type="text"]').on('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    loadResults($form.attr('action'));
                }, 400);
            });

            $results.on('click', '.pagination a', function (event) {
                event.preventDefault();
                loadResults($(this).attr('href'));
            });
        });
    </script>
@endsection
