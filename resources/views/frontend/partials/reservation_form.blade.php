@php
$reservationShouldOpen = session('success') || $errors->any();
@endphp
<div class="aparto-reservation-stash" data-reservation-stash>
    <div class="aparto-detail-card aparto-reservation-card is-hidden" data-reservation-card data-open="{{ $reservationShouldOpen ? 'true' : 'false' }}">
        <h2 class="aparto-reservation-title">{{ __('frontpage.reservation.title') }}</h2>
        <p class="aparto-hero-subtitle">{{ __('frontpage.reservation.subtitle') }}</p>

        @if(session('success'))
        <div class="aparto-form-message is-success">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="aparto-form-message is-error">
            <strong>{{ __('frontpage.reservation.error_title') }}</strong>
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form class="aparto-reservation-form"
            data-reservation-ranges='@json($reservationRanges)'
            data-custom-pricing='@json($customPricing ?? [])'
            data-price-per-night="{{ $apartment->price_per_night }}"
            data-min-nights="{{ $apartment->min_nights ?? 1 }}"
            data-discount-nights="{{ $apartment->discount_nights ?? 0 }}"
            data-discount-percentage="{{ $apartment->discount_percentage ?? 0 }}"
            data-deposit-rate="{{ config('website.deposit_rate', 0.3) }}"
            data-currency="{{ config('website.currency') }}"
            data-nights-label="{{ __('frontpage.reservation.nights') }}"
            data-deposit-label="{{ __('frontpage.reservation.deposit_label') }}"
            data-pay-label="{{ __('frontpage.reservation.pay_label') }}"
            method="POST"
            action="{{ route('reserve', $apartment) }}">
            @csrf
            <div class="aparto-filter-row">
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="date_from">{{ __('frontpage.reservation.date_from') }}</label>
                    <input class="aparto-filter-input" type="date" id="date_from" name="date_from" value="{{ old('date_from') }}" required>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="date_to">{{ __('frontpage.reservation.date_to') }}</label>
                    <input class="aparto-filter-input" type="date" id="date_to" name="date_to" value="{{ old('date_to') }}" required>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="name">{{ __('frontpage.reservation.name') }}</label>
                    <input class="aparto-filter-input" type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="email">{{ __('frontpage.reservation.email') }}</label>
                    <input class="aparto-filter-input" type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="aparto-filter-field">
                    <label class="aparto-filter-label" for="phone">{{ __('frontpage.reservation.phone') }}</label>
                    <input class="aparto-filter-input" type="text" id="phone" name="phone" value="{{ old('phone') }}" required>
                </div>
                <div class="aparto-filter-field aparto-filter-field--full">
                    <label class="aparto-filter-label" for="note">{{ __('frontpage.reservation.note') }}</label>
                    <textarea class="aparto-filter-input aparto-filter-textarea" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                </div>
            </div>
            <div class="aparto-reservation-total">
                <span class="aparto-reservation-total-label">{{ __('frontpage.reservation.total_label') }}</span>
                <span class="aparto-reservation-total-value" data-reservation-total-value>{{ config('website.currency') }} 0.00</span>
                <span class="aparto-reservation-total-meta" data-reservation-total-meta></span>
                <div class="aparto-reservation-total-row">
                    <span class="aparto-reservation-total-caption" data-reservation-deposit-label></span>
                    <span class="aparto-reservation-total-amount" data-reservation-deposit-value>{{ config('website.currency') }} 0.00</span>
                </div>
                <div class="aparto-reservation-total-row is-strong">
                    <span class="aparto-reservation-total-caption" data-reservation-pay-label></span>
                    <span class="aparto-reservation-total-amount" data-reservation-pay-value>{{ config('website.currency') }} 0.00</span>
                </div>
            </div>
            <div class="aparto-filter-actions">
                <button class="aparto-button primary" type="submit">{{ __('frontpage.reservation.submit') }}</button>
                <button class="aparto-button ghost" type="button" data-reserve-cancel>{{ __('frontpage.reservation.cancel') }}</button>
            </div>
        </form>
    </div>
</div>