<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Models\Review;

class Apartment extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (self $apartment): void {
            if (!$apartment->wasChanged('blocked_dates') && !$apartment->wasRecentlyCreated) {
                return;
            }

            $apartment->syncBlockedPeriodsFromJson();
        });
    }

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'city',
        'address',
        'latitude',
        'longitude',
        'price_per_night',
        'min_nights',
        'discount_nights',
        'discount_percentage',
        'blocked_dates',
        'custom_pricing',
        'rooms',
        'guest_number',
        'active',
        'parking',
        'wifi',
        'pet_friendly',
        'lead_image',
        'gallery_images',
    ];

    protected $casts = [
        'parking' => 'boolean',
        'wifi' => 'boolean',
        'pet_friendly' => 'boolean',
        'gallery_images' => 'array',
        'blocked_dates' => 'array',
        'custom_pricing' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'guest_number' => 'integer',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function blockedPeriods()
    {
        return $this->hasMany(ApartmentBlockedPeriod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAvailable($from, $to)
    {
        // Check if dates are blocked
        if ($this->isBlocked($from, $to)) {
            return false;
        }

        // Optimized query with proper date overlap check
        // For hotel bookings: check-out date is exclusive, check-in is inclusive
        // So if existing reservation ends on 2026-03-05, new can start on 2026-03-05
        return !$this->reservations()
            ->where('status', '!=', 'canceled')
            ->where('date_from', '<', $to)
            ->where('date_to', '>', $from)
            ->exists();
    }

    public function isBlocked($from, $to)
    {
        $fromDate = \Carbon\Carbon::parse($from)->startOfDay();
        $toDate = \Carbon\Carbon::parse($to)->startOfDay();

        // Treat invalid/zero-length ranges as a 1-night request.
        if ($toDate->lte($fromDate)) {
            $toDate = $fromDate->copy()->addDay();
        }

        // Blocked ranges are inclusive in admin UI, while check-out is exclusive.
        // Overlap formula: blocked_from < requested_to AND blocked_to >= requested_from.
        $hasBlockedPeriod = $this->blockedPeriods()
            ->where('date_from', '<', $toDate->toDateString())
            ->where('date_to', '>=', $fromDate->toDateString())
            ->exists();

        if ($hasBlockedPeriod) {
            return true;
        }

        // Legacy fallback while old JSON column is still present.
        if (empty($this->blocked_dates)) {
            return false;
        }

        foreach ($this->blocked_dates as $blocked) {
            $blockedFromRaw = $blocked['from'] ?? $blocked['date_from'] ?? null;
            $blockedToRaw = $blocked['to'] ?? $blocked['date_to'] ?? null;

            if (!$blockedFromRaw || !$blockedToRaw) {
                continue;
            }

            $blockedFrom = \Carbon\Carbon::parse($blockedFromRaw)->startOfDay();
            // Admin blocked ranges are calendar-date inclusive.
            $blockedToExclusive = \Carbon\Carbon::parse($blockedToRaw)->startOfDay()->addDay();

            if ($fromDate->lt($blockedToExclusive) && $toDate->gt($blockedFrom)) {
                return true;
            }
        }

        return false;
    }

    public function syncBlockedPeriodsFromJson(): void
    {
        $periods = collect($this->blocked_dates ?? [])
            ->map(function ($blocked) {
                if (!is_array($blocked)) {
                    return null;
                }

                $from = $blocked['from'] ?? $blocked['date_from'] ?? null;
                $to = $blocked['to'] ?? $blocked['date_to'] ?? null;

                if (!$from || !$to) {
                    return null;
                }

                try {
                    $fromDate = \Carbon\Carbon::parse($from)->toDateString();
                    $toDate = \Carbon\Carbon::parse($to)->toDateString();
                } catch (\Throwable $e) {
                    return null;
                }

                if ($toDate < $fromDate) {
                    return null;
                }

                return [
                    'date_from' => $fromDate,
                    'date_to' => $toDate,
                ];
            })
            ->filter()
            ->values();

        $this->blockedPeriods()->delete();

        if ($periods->isNotEmpty()) {
            $this->blockedPeriods()->createMany($periods->all());
        }
    }

    public function calculatePrice($from, $to)
    {
        $fromDate = \Carbon\Carbon::parse($from);
        $toDate = \Carbon\Carbon::parse($to);
        $nights = $fromDate->diffInDays($toDate);

        if ($nights <= 0) {
            return [
                'nights' => 0,
                'total' => 0,
                'price_per_night' => $this->price_per_night,
                'discount_applied' => false,
                'discount_amount' => 0,
            ];
        }

        // Prevent performance issues with extremely long reservations
        if ($nights > 365) {
            Log::warning('Very long reservation requested', [
                'apartment_id' => $this->id,
                'nights' => $nights,
                'date_from' => $fromDate->toDateString(),
                'date_to' => $toDate->toDateString(),
            ]);
            // For very long stays, use simplified calculation
            $total = $this->price_per_night * $nights;
        } else {
            $total = 0;
            $currentDate = $fromDate->copy();

            // Calculate total considering custom pricing
            while ($currentDate->lt($toDate)) {
                $dateStr = $currentDate->toDateString();
                $priceForDate = $this->getPriceForDate($dateStr);
                $total += $priceForDate;
                $currentDate->addDay();
            }
        }

        // Apply discount if applicable
        $discountApplied = false;
        $discountAmount = 0;

        if ($this->discount_nights && $this->discount_percentage && $nights >= $this->discount_nights) {
            $discountAmount = ($total * $this->discount_percentage) / 100;
            $total -= $discountAmount;
            $discountApplied = true;
        }

        return [
            'nights' => $nights,
            'total' => round($total, 2),
            'price_per_night' => round($total / $nights, 2),
            'discount_applied' => $discountApplied,
            'discount_amount' => round($discountAmount, 2),
        ];
    }

    public function getPriceForDate($date)
    {
        $dateStr = \Carbon\Carbon::parse($date)->toDateString();

        // Check custom pricing
        if (!empty($this->custom_pricing)) {
            foreach ($this->custom_pricing as $pricing) {
                if (!isset($pricing['from']) || !isset($pricing['to']) || !isset($pricing['price'])) {
                    continue;
                }

                $priceFrom = \Carbon\Carbon::parse($pricing['from'])->toDateString();
                $priceTo = \Carbon\Carbon::parse($pricing['to'])->toDateString();

                if ($dateStr >= $priceFrom && $dateStr <= $priceTo) {
                    return (float) $pricing['price'];
                }
            }
        }

        return $this->price_per_night;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->reviews()->where('status', 'approved');
    }

    public function getAverageRatingAttribute($value)
    {
        if ($value !== null) {
            return round((float) $value, 1);
        }

        $average = $this->approvedReviews()->avg('rating');

        return $average !== null ? round((float) $average, 1) : 0.0;
    }

    public function getReviewsCountAttribute($value)
    {
        if ($value !== null) {
            return (int) $value;
        }

        return $this->approvedReviews()->count();
    }


}
