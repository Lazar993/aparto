<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return !$this->reservations()
            ->where('status', '!=', 'canceled')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('date_from', [$from, $to])
                    ->orWhereBetween('date_to', [$from, $to])
                    ->orWhere(function ($q) use ($from, $to) {
                        $q->where('date_from', '<=', $from)
                        ->where('date_to', '>=', $to);
                    });
            })
            ->exists();
    }


}
