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

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
}

