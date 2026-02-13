@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')

    <section class="aparto-fade-up aparto-delay-1">
        <h1 class="aparto-section-title">{{ __('frontpage.apartments.title') }}</h1>
        <p class="aparto-hero-subtitle">{{ __('frontpage.apartments.subtitle') }}</p>

        <form class="aparto-filter" method="GET" action="{{ route('apartments.index') }}">
            <div class="aparto-filter-row">
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="filter-q">{{ __('frontpage.filters.search') }}</label>
                    <input id="filter-q" name="q" type="text" value="{{ request('q') }}" placeholder="{{ __('frontpage.filters.search_placeholder') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="filter-city">{{ __('frontpage.filters.city') }}</label>
                    <select id="filter-city" name="city" class="aparto-filter-input">
                        <option value="">{{ __('frontpage.filters.all_cities') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="filter-min">{{ __('frontpage.filters.min_price') }}</label>
                    <input id="filter-min" name="min_price" type="number" step="1" min="0" value="{{ request('min_price') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="filter-max">{{ __('frontpage.filters.max_price') }}</label>
                    <input id="filter-max" name="max_price" type="number" step="1" min="0" value="{{ request('max_price') }}" class="aparto-filter-input">
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="filter-parking">{{ __('frontpage.filters.parking') }}</label>
                    <select id="filter-parking" name="parking" class="aparto-filter-input">
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

            $form.on('submit', function (event) {
                event.preventDefault();
                loadResults($form.attr('action'));
            });

            $form.find('select, input[type="number"]').on('change', function () {
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
