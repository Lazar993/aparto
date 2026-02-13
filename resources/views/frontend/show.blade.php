@extends('layouts.app')

@section('content')
    
    @include('layouts.partials.header')

    @include('frontend.partials.apartment_single', ['apartment' => $apartment])

    @include('frontend.partials.reservation_form', ['apartment' => $apartment])

    @if($apartment->latitude && $apartment->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var mapEl = document.getElementById('aparto-detail-map');
                if (!mapEl || mapEl.dataset.mapReady === 'true') {
                    return;
                }

                var lat = parseFloat(mapEl.dataset.lat);
                var lng = parseFloat(mapEl.dataset.lng);

                if (isNaN(lat) || isNaN(lng)) {
                    return;
                }

                var map = L.map(mapEl).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                L.marker([lat, lng]).addTo(map);
                mapEl.dataset.mapReady = 'true';
            });
        </script>
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('.aparto-reservation-form');
            if (!form || !window.flatpickr) {
                return;
            }

            var ranges = [];
            try {
                ranges = JSON.parse(form.dataset.reservationRanges || '[]');
            } catch (error) {
                ranges = [];
            }

            function isDisabled(date) {
                var day = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();

                return ranges.some(function (range) {
                    if (!range.from || !range.to) {
                        return false;
                    }

                    var start = new Date(range.from + 'T00:00:00');
                    var end = new Date(range.to + 'T00:00:00');

                    return day >= start.getTime() && day < end.getTime();
                });
            }

            var totalValue = form.querySelector('[data-reservation-total-value]');
            var totalMeta = form.querySelector('[data-reservation-total-meta]');
            var depositLabel = form.querySelector('[data-reservation-deposit-label]');
            var depositValue = form.querySelector('[data-reservation-deposit-value]');
            var payLabel = form.querySelector('[data-reservation-pay-label]');
            var payValue = form.querySelector('[data-reservation-pay-value]');
            var pricePerNight = parseFloat(form.dataset.pricePerNight || '0');
            var currency = form.dataset.currency || 'EUR';
            var nightsLabel = form.dataset.nightsLabel || '';
            var depositRate = parseFloat(form.dataset.depositRate || '0.3');
            var depositText = form.dataset.depositLabel || '';
            var payText = form.dataset.payLabel || '';

            if (depositLabel) {
                depositLabel.textContent = depositText;
            }
            if (payLabel) {
                payLabel.textContent = payText;
            }

            function updateTotal() {
                var fromValue = form.querySelector('#date_from').value;
                var toValue = form.querySelector('#date_to').value;

                if (!fromValue || !toValue) {
                    if (totalValue) {
                        totalValue.textContent = currency + ' 0.00';
                    }
                    if (totalMeta) {
                        totalMeta.textContent = '';
                    }
                    if (depositValue) {
                        depositValue.textContent = currency + ' 0.00';
                    }
                    if (payValue) {
                        payValue.textContent = currency + ' 0.00';
                    }
                    return;
                }

                var fromDate = new Date(fromValue + 'T00:00:00');
                var toDate = new Date(toValue + 'T00:00:00');
                var nights = Math.round((toDate - fromDate) / 86400000);

                if (!Number.isFinite(nights) || nights <= 0) {
                    if (totalValue) {
                        totalValue.textContent = currency + ' 0.00';
                    }
                    if (totalMeta) {
                        totalMeta.textContent = '';
                    }
                    if (depositValue) {
                        depositValue.textContent = currency + ' 0.00';
                    }
                    if (payValue) {
                        payValue.textContent = currency + ' 0.00';
                    }
                    return;
                }

                var total = nights * pricePerNight;
                var deposit = Math.round(total * depositRate * 100) / 100;
                if (totalValue) {
                    totalValue.textContent = currency + ' ' + total.toFixed(2);
                }
                if (totalMeta) {
                    totalMeta.textContent = nights + ' ' + nightsLabel + ' x ' + currency + ' ' + pricePerNight;
                }
                if (depositValue) {
                    depositValue.textContent = currency + ' ' + deposit.toFixed(2);
                }
                if (payValue) {
                    payValue.textContent = currency + ' ' + deposit.toFixed(2);
                }
            }

            var dateTo = null;
            var dateFrom = flatpickr(form.querySelector('#date_from'), {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disable: [isDisabled],
                onChange: function (selectedDates) {
                    if (selectedDates[0] && dateTo) {
                        dateTo.set('minDate', selectedDates[0]);
                    }

                    updateTotal();
                }
            });

            dateTo = flatpickr(form.querySelector('#date_to'), {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disable: [isDisabled],
                onChange: function () {
                    updateTotal();
                }
            });

            form._updateTotal = updateTotal;
            updateTotal();
        });
    </script>
    @if(!empty($apartment->gallery_images))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var gallery = document.querySelector('[data-gallery]');
                var toggle = document.querySelector('[data-gallery-toggle]');
                if (!gallery) {
                    return;
                }

                if (toggle) {
                    toggle.addEventListener('click', function () {
                        gallery.classList.toggle('is-hidden');
                        var isHidden = gallery.classList.contains('is-hidden');
                        toggle.textContent = isHidden ? toggle.dataset.showText : toggle.dataset.hideText;
                        toggle.classList.toggle('is-active', !isHidden);
                    });
                }

                var track = gallery.querySelector('[data-gallery-track]');
                var slides = track ? track.children : [];
                var prev = gallery.querySelector('[data-gallery-prev]');
                var next = gallery.querySelector('[data-gallery-next]');
                var dotsWrap = gallery.querySelector('[data-gallery-dots]');
                var index = 0;

                if (!track || slides.length === 0) {
                    return;
                }

                function renderDots() {
                    if (!dotsWrap) {
                        return;
                    }

                    dotsWrap.innerHTML = '';
                    for (var i = 0; i < slides.length; i += 1) {
                        var dot = document.createElement('span');
                        dot.className = 'aparto-gallery-dot' + (i === index ? ' is-active' : '');
                        dotsWrap.appendChild(dot);
                    }
                }

                function update() {
                    track.style.transform = 'translateX(' + (-index * 100) + '%)';
                    renderDots();
                }

                if (prev) {
                    prev.addEventListener('click', function () {
                        index = (index - 1 + slides.length) % slides.length;
                        update();
                    });
                }

                if (next) {
                    next.addEventListener('click', function () {
                        index = (index + 1) % slides.length;
                        update();
                    });
                }

                update();
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('[data-description-toggle]');
            var description = document.querySelector('[data-description]');

            if (!toggle || !description) {
                return;
            }

            toggle.dataset.showText = '{{ __('frontpage.detail.show_description') }}';
            toggle.dataset.hideText = '{{ __('frontpage.detail.hide_description') }}';

            toggle.addEventListener('click', function () {
                var isHidden = description.classList.contains('is-hidden');
                description.classList.toggle('is-hidden');
                toggle.classList.toggle('is-active', isHidden);
                toggle.textContent = isHidden ? toggle.dataset.hideText : toggle.dataset.showText;
                if (isHidden) {
                    description.style.animation = 'none';
                    description.offsetHeight;
                    description.style.animation = '';
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var reserveSection = document.querySelector('.aparto-detail');
            var reserveStash = document.querySelector('[data-reservation-stash]');
            var reserveCard = reserveStash ? reserveStash.querySelector('[data-reservation-card]') : null;
            if (!reserveSection || !reserveCard) {
                return;
            }

            function insertReserveCard() {
                if (reserveSection.contains(reserveCard)) {
                    return;
                }

                var mediaCard = reserveSection.querySelector('.aparto-detail-media');
                var targetCard = mediaCard ? mediaCard.closest('.aparto-detail-card') : null;

                if (targetCard) {
                    reserveSection.insertBefore(reserveCard, targetCard);
                    return;
                }

                reserveSection.appendChild(reserveCard);
            }

            function showReserveForm() {
                insertReserveCard();
                reserveCard.classList.remove('is-hidden');
                reserveCard.classList.remove('is-visible');
                reserveCard.offsetHeight;
                requestAnimationFrame(function () {
                    reserveCard.classList.add('is-visible');
                });
            }

            function hideReserveForm() {
                reserveCard.classList.remove('is-visible');
                setTimeout(function () {
                    reserveCard.classList.add('is-hidden');
                }, 360);
            }

            function resetReserveForm() {
                var form = reserveCard.querySelector('form');
                if (form) {
                    form.reset();
                    if (typeof form._updateTotal === 'function') {
                        form._updateTotal();
                    }
                }
            }

            if (reserveCard.dataset.open === 'true') {
                showReserveForm();
            }

            var reserveToggle = document.querySelector('[data-reserve-toggle]');
            if (reserveToggle) {
                reserveToggle.addEventListener('click', function () {
                    showReserveForm();
                });
            }

            var reserveCancel = reserveCard.querySelector('[data-reserve-cancel]');
            if (reserveCancel) {
                reserveCancel.addEventListener('click', function () {
                    resetReserveForm();
                    hideReserveForm();
                });
            }
        });
    </script>
@endsection
