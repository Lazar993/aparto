<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Review;

class Apartment extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAvailable($from, $to)
    {
        // Check if dates are blocked
        if ($this->isBlocked($from, $to)) {
            \Log::info('Apartment blocked', [
                'apartment_id' => $this->id,
                'from' => $from,
                'to' => $to,
            ]);
            return false;
        }

        // Optimized query with proper date overlap check
        // For hotel bookings: check-out date is exclusive, check-in is inclusive
        // So if existing reservation ends on 2026-03-05, new can start on 2026-03-05
        $overlappingReservations = $this->reservations()
            ->where('status', '!=', 'canceled')
            ->where('date_from', '<', $to)
            ->where('date_to', '>', $from)
            ->get();

        if ($overlappingReservations->isNotEmpty()) {
            \Log::warning('Overlapping reservations found', [
                'apartment_id' => $this->id,
                'requested_from' => $from,
                'requested_to' => $to,
                'overlapping_count' => $overlappingReservations->count(),
                'overlapping_reservations' => $overlappingReservations->map(function($r) {
                    return [
                        'id' => $r->id,
                        'date_from' => $r->date_from,
                        'date_to' => $r->date_to,
                        'status' => $r->status,
                    ];
                })->toArray(),
            ]);
            return false;
        }

        \Log::info('Apartment is available', [
            'apartment_id' => $this->id,
            'from' => $from,
            'to' => $to,
        ]);

        return true;
    }

    public function isBlocked($from, $to)
    {
        if (empty($this->blocked_dates)) {
            return false;
        }

        $fromDate = \Carbon\Carbon::parse($from);
        $toDate = \Carbon\Carbon::parse($to);

        foreach ($this->blocked_dates as $blocked) {
            if (!isset($blocked['from']) || !isset($blocked['to'])) {
                continue;
            }

            $blockedFrom = \Carbon\Carbon::parse($blocked['from']);
            $blockedTo = \Carbon\Carbon::parse($blocked['to']);

            // Check if booking period overlaps with blocked period
            if ($fromDate->lt($blockedTo) && $toDate->gt($blockedFrom)) {
                return true;
            }
        }

        return false;
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
            \Log::warning('Very long reservation requested', [
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

    public function getAverageRatingAttribute()
    {
        return round($this->approvedReviews()->avg('rating'), 1);
    }

    public function getReviewsCountAttribute()
    {
        return $this->approvedReviews()->count();
    }


}
