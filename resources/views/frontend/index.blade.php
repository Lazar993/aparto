@extends('layouts.app')

@section('content')

    @include('layouts.partials.header')
    @include('frontend.partials.main')
    @include('frontend.partials.search')
    @include('frontend.partials.apartments', ['apartments' => $apartments])
    @include('frontend.partials.info')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var $form = $('#home-aparto-filter');
            var $results = $('#home-aparto-results');
            var debounceTimer = null;

            if (!$form.length || !$results.length) {
                return;
            }

            function loadResults(url) {
                $results.addClass('is-loading');

                $.ajax({
                    url: url || $form.attr('action'),
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
                loadResults();
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

                loadResults();
            });

            $form.find('select, input[type="number"], input[type="date"]').on('change', function () {
                syncDateBounds();
                loadResults();
            });

            $form.find('input[type="text"]').on('input', function () {
                clearTimeout(debounceTimer);

                debounceTimer = setTimeout(function () {
                    loadResults();
                }, 400);
            });

            $results.on('click', '.pagination a', function (event) {
                event.preventDefault();
                loadResults($(this).attr('href'));
            });
        });
    </script>
    
@endsection
