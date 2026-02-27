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
    <style>
        /* Flatpickr price display styles */
        .flatpickr-day {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: 4px;
            height: auto !important;
            min-height: 48px;
            border-radius: 4px !important; /* Square shape with slight rounding */
        }
        
        .flatpickr-day:hover,
        .flatpickr-day.prevMonthDay:hover,
        .flatpickr-day.nextMonthDay:hover {
            border-radius: 4px !important;
        }
        
        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected.inRange,
        .flatpickr-day.startRange.inRange,
        .flatpickr-day.endRange.inRange,
        .flatpickr-day.selected:focus,
        .flatpickr-day.startRange:focus,
        .flatpickr-day.endRange:focus,
        .flatpickr-day.selected:hover,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover,
        .flatpickr-day.selected.prevMonthDay,
        .flatpickr-day.startRange.prevMonthDay,
        .flatpickr-day.endRange.prevMonthDay,
        .flatpickr-day.selected.nextMonthDay,
        .flatpickr-day.startRange.nextMonthDay,
        .flatpickr-day.endRange.nextMonthDay {
            border-radius: 4px !important;
        }
        
        .flatpickr-day.inRange {
            border-radius: 0 !important;
            box-shadow: -5px 0 0 #e6e6e6, 5px 0 0 #e6e6e6;
        }
        
        .flatpickr-day-price {
            font-size: 9px;
            font-weight: 600;
            color: #666;
            margin-top: 2px;
            line-height: 1;
        }
        
        .flatpickr-day.has-custom-price .flatpickr-day-price {
            color: #ff6b35;
            font-weight: 700;
        }
        
        .flatpickr-day.selected .flatpickr-day-price,
        .flatpickr-day.startRange .flatpickr-day-price,
        .flatpickr-day.endRange .flatpickr-day-price,
        .flatpickr-day.today.selected .flatpickr-day-price {
            color: white;
        }
        
        .flatpickr-day.disabled .flatpickr-day-price {
            display: none;
        }
        
        .flatpickr-day:hover .flatpickr-day-price {
            color: #333;
        }
        
        .flatpickr-day.has-custom-price:hover .flatpickr-day-price {
            color: #ff4500;
        }
        
        .flatpickr-day.selected:hover .flatpickr-day-price,
        .flatpickr-day.startRange:hover .flatpickr-day-price,
        .flatpickr-day.endRange:hover .flatpickr-day-price {
            color: white;
        }
    </style>
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
            var minNights = parseInt(form.dataset.minNights || '1');
            var discountNights = parseInt(form.dataset.discountNights || '0');
            var discountPercentage = parseFloat(form.dataset.discountPercentage || '0');
            var currency = form.dataset.currency || 'EUR';
            var nightsLabel = form.dataset.nightsLabel || '';
            var depositRate = parseFloat(form.dataset.depositRate || '0.3');
            var depositText = form.dataset.depositLabel || '';
            var payText = form.dataset.payLabel || '';
            var customPricing = [];
            
            try {
                customPricing = JSON.parse(form.dataset.customPricing || '[]');
            } catch (error) {
                customPricing = [];
            }

            if (depositLabel) {
                depositLabel.textContent = depositText;
            }
            if (payLabel) {
                payLabel.textContent = payText;
            }

            function getPriceForDate(dateStr) {
                // Check if date falls within any custom pricing period
                for (var i = 0; i < customPricing.length; i++) {
                    var pricing = customPricing[i];
                    if (!pricing.from || !pricing.to || !pricing.price) {
                        continue;
                    }
                    
                    if (dateStr >= pricing.from && dateStr <= pricing.to) {
                        return parseFloat(pricing.price);
                    }
                }
                
                return pricePerNight;
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

                // Calculate total considering custom pricing
                var total = 0;
                var currentDate = new Date(fromDate);
                
                while (currentDate < toDate) {
                    var dateStr = currentDate.toISOString().split('T')[0];
                    total += getPriceForDate(dateStr);
                    currentDate.setDate(currentDate.getDate() + 1);
                }

                var avgPricePerNight = total / nights;
                var discountAmount = 0;
                var discountApplied = false;

                // Apply discount if applicable
                if (discountNights > 0 && discountPercentage > 0 && nights >= discountNights) {
                    discountAmount = (total * discountPercentage) / 100;
                    total -= discountAmount;
                    discountApplied = true;
                }

                var deposit = Math.round(total * depositRate * 100) / 100;
                
                if (totalValue) {
                    totalValue.textContent = currency + ' ' + total.toFixed(2);
                }
                if (totalMeta) {
                    var metaText = nights + ' ' + nightsLabel;
                    if (discountApplied) {
                        metaText += ' (' + discountPercentage + '% discount applied)';
                    }
                    totalMeta.textContent = metaText;
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
                        // Set minimum check-out date to be check-in date + minimum nights
                        var minCheckout = new Date(selectedDates[0]);
                        minCheckout.setDate(minCheckout.getDate() + minNights);
                        dateTo.set('minDate', minCheckout);
                        
                        // Clear check-out if it's now before the new minimum
                        var currentCheckout = dateTo.selectedDates[0];
                        if (currentCheckout && currentCheckout < minCheckout) {
                            dateTo.clear();
                        }
                    }

                    updateTotal();
                },
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    var dateStr = dayElem.dateObj.toISOString().split('T')[0];
                    var price = getPriceForDate(dateStr);
                    var isUnavailable = isDisabled(dayElem.dateObj);
                    
                    if (!isUnavailable) {
                        var priceSpan = document.createElement('span');
                        priceSpan.className = 'flatpickr-day-price';
                        priceSpan.textContent = currency + ' ' + price.toFixed(0);
                        dayElem.appendChild(priceSpan);
                        
                        // Highlight if different from base price
                        if (price !== pricePerNight) {
                            dayElem.classList.add('has-custom-price');
                        }
                    }
                }
            });

            dateTo = flatpickr(form.querySelector('#date_to'), {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disable: [isDisabled],
                onChange: function () {
                    updateTotal();
                },
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    var dateStr = dayElem.dateObj.toISOString().split('T')[0];
                    var price = getPriceForDate(dateStr);
                    var isUnavailable = isDisabled(dayElem.dateObj);
                    
                    if (!isUnavailable) {
                        var priceSpan = document.createElement('span');
                        priceSpan.className = 'flatpickr-day-price';
                        priceSpan.textContent = currency + ' ' + price.toFixed(0);
                        dayElem.appendChild(priceSpan);
                        
                        // Highlight if different from base price
                        if (price !== pricePerNight) {
                            dayElem.classList.add('has-custom-price');
                        }
                    }
                }
            });

            form._updateTotal = updateTotal;
            updateTotal();
            
            // Add form validation on submit
            form.addEventListener('submit', function(e) {
                var fromValue = form.querySelector('#date_from').value;
                var toValue = form.querySelector('#date_to').value;
                
                if (!fromValue || !toValue) {
                    e.preventDefault();
                    alert('Please select both check-in and check-out dates.');
                    return false;
                }
                
                var fromDate = new Date(fromValue + 'T00:00:00');
                var toDate = new Date(toValue + 'T00:00:00');
                var nights = Math.round((toDate - fromDate) / 86400000);
                
                if (nights < minNights) {
                    e.preventDefault();
                    alert('This apartment requires a minimum of ' + minNights + ' night(s). You have selected ' + nights + ' night(s). Please select a longer stay.');
                    return false;
                }
            });
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

                // Lightbox functionality
                var lightbox = document.querySelector('[data-lightbox]');
                var lightboxImage = lightbox ? lightbox.querySelector('[data-lightbox-image]') : null;
                var lightboxClose = lightbox ? lightbox.querySelector('[data-lightbox-close]') : null;
                var lightboxPrev = lightbox ? lightbox.querySelector('[data-lightbox-prev]') : null;
                var lightboxNext = lightbox ? lightbox.querySelector('[data-lightbox-next]') : null;
                var lightboxCounter = lightbox ? lightbox.querySelector('[data-lightbox-counter]') : null;
                var lightboxIndex = 0;
                var images = Array.from(slides).map(function(slide) {
                    return slide.querySelector('img').src;
                });

                function openLightbox(imageIndex) {
                    if (!lightbox || !lightboxImage) {
                        return;
                    }
                    lightboxIndex = imageIndex;
                    lightboxImage.src = images[lightboxIndex];
                    updateLightboxCounter();
                    lightbox.classList.add('is-active');
                    document.body.style.overflow = 'hidden';
                }

                function closeLightbox() {
                    if (!lightbox) {
                        return;
                    }
                    lightbox.classList.remove('is-active');
                    document.body.style.overflow = '';
                }

                function updateLightboxCounter() {
                    if (lightboxCounter) {
                        lightboxCounter.textContent = (lightboxIndex + 1) + ' / ' + images.length;
                    }
                }

                function showPrevLightboxImage() {
                    lightboxIndex = (lightboxIndex - 1 + images.length) % images.length;
                    lightboxImage.src = images[lightboxIndex];
                    updateLightboxCounter();
                }

                function showNextLightboxImage() {
                    lightboxIndex = (lightboxIndex + 1) % images.length;
                    lightboxImage.src = images[lightboxIndex];
                    updateLightboxCounter();
                }

                // Add click listeners to gallery images
                Array.from(slides).forEach(function(slide, idx) {
                    var img = slide.querySelector('img');
                    if (img) {
                        img.addEventListener('click', function() {
                            openLightbox(idx);
                        });
                    }
                });

                // Lightbox controls
                if (lightboxClose) {
                    lightboxClose.addEventListener('click', closeLightbox);
                }

                if (lightboxPrev) {
                    lightboxPrev.addEventListener('click', showPrevLightboxImage);
                }

                if (lightboxNext) {
                    lightboxNext.addEventListener('click', showNextLightboxImage);
                }

                // Close lightbox on background click
                if (lightbox) {
                    lightbox.addEventListener('click', function(e) {
                        if (e.target === lightbox) {
                            closeLightbox();
                        }
                    });
                }

                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (!lightbox.classList.contains('is-active')) {
                        return;
                    }

                    if (e.key === 'Escape') {
                        closeLightbox();
                    } else if (e.key === 'ArrowLeft') {
                        showPrevLightboxImage();
                    } else if (e.key === 'ArrowRight') {
                        showNextLightboxImage();
                    }
                });
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
        // Reviews toggle
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('[data-reviews-toggle]');
            var reviews = document.querySelector('[data-reviews]');

            if (!toggle || !reviews) {
                return;
            }

            toggle.dataset.showText = '{{ __('frontpage.reviews.show') }}';
            toggle.dataset.hideText = '{{ __('frontpage.reviews.hide') }}';

            toggle.addEventListener('click', function () {
                var isHidden = reviews.classList.contains('is-hidden');
                reviews.classList.toggle('is-hidden');
                toggle.classList.toggle('is-active', isHidden);
                toggle.textContent = isHidden ? toggle.dataset.hideText : toggle.dataset.showText;
                if (isHidden) {
                    reviews.style.animation = 'none';
                    reviews.offsetHeight;
                    reviews.style.animation = '';
                }
            });
        });

        // Star rating selector
        document.addEventListener('DOMContentLoaded', function () {
            var ratingInput = document.querySelector('[data-rating-input]');
            if (!ratingInput) {
                return;
            }

            var stars = ratingInput.querySelectorAll('.aparto-star-input');
            var hiddenInput = document.getElementById('rating-value');
            var selectedRating = 0;

            stars.forEach(function(star, index) {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(star.dataset.rating);
                    hiddenInput.value = selectedRating;
                    updateStars(selectedRating);
                });

                star.addEventListener('mouseenter', function() {
                    var hoverRating = parseInt(star.dataset.rating);
                    updateStars(hoverRating);
                });
            });

            ratingInput.addEventListener('mouseleave', function() {
                updateStars(selectedRating);
            });

            function updateStars(rating) {
                stars.forEach(function(star, index) {
                    if (index < rating) {
                        star.classList.add('hovered');
                    } else {
                        star.classList.remove('hovered');
                    }
                });
            }
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
                    // Scroll to the reservation card
                    setTimeout(function() {
                        if (reserveCard) {
                            reserveCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 400); // Wait for form to be inserted and animated
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
