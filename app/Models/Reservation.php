<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'apartment_id',
        'user_id',
        'name',
        'email',
        'phone',
        'date_from',
        'date_to',
        'nights',
        'price_per_night',
        'total_price',
        'deposit_amount',
        'status',
        'payment_provider',
        'payment_reference',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'paid_at' => 'datetime',
        'price_per_night' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

