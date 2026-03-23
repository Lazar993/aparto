@if($host)
<div class="aparto-host-card">
    <h3 class="aparto-host-title">{{ __('frontpage.host.meet_title') }}</h3>
    <div class="aparto-host-body">
        <a href="{{ route('host.profile', $host->id) }}" class="aparto-host-avatar">
            @if($host->profile_image)
                <img src="{{ asset('storage/' . $host->profile_image) }}" alt="{{ $host->name }}">
            @else
                <img src="{{ asset('images/default-profile-image.png') }}" alt="{{ $host->name }}">
            @endif
        </a>
        <div class="aparto-host-info">
            <a href="{{ route('host.profile', $host->id) }}" class="aparto-host-name">{{ $host->name }}</a>
        </div>
        <div class="aparto-host-badges">
            @if($hostAverageRating)
                <div class="aparto-host-badge">
                    <span class="aparto-host-badge-value">★ {{ $hostAverageRating }}</span>
                    <span class="aparto-host-badge-label">{{ __('frontpage.host.rating') }}</span>
                </div>
            @endif
            @if($hostTotalReviews > 0)
                <div class="aparto-host-badge">
                    <span class="aparto-host-badge-value">{{ $hostTotalReviews }}</span>
                    <span class="aparto-host-badge-label">
                        @if($hostTotalReviews == 1)
                            {{ __('frontpage.host.reviews_one') }}
                        @elseif($hostTotalReviews < 5)
                            {{ __('frontpage.host.reviews_few') }}
                        @else
                            {{ __('frontpage.host.reviews_many') }}
                        @endif
                    </span>
                </div>
            @endif
            @if(($hostApartmentsCount ?? 0) > 0)
                <div class="aparto-host-badge">
                    <span class="aparto-host-badge-value">{{ $hostApartmentsCount }}</span>
                    @if($hostApartmentsCount == 1)
                        <span class="aparto-host-badge-label">{{ __('frontpage.host.apartments_count_one') }}</span>
                    @elseif($hostApartmentsCount < 5)
                        <span class="aparto-host-badge-label">{{ __('frontpage.host.apartments_count_few') }}</span>
                    @else                        
                        <span class="aparto-host-badge-label">{{ __('frontpage.host.apartments_count_many') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <a href="{{ route('host.profile', $host->id) }}" class="aparto-host-view-link">
        {{ __('frontpage.host.view_profile') }} →
    </a>
</div>
@endif
