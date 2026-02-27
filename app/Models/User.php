<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use App\Models\Review;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Check if user has admin flag OR has admin roles
        if ($this->is_admin) {
            return true;
        }
        
        return $this->hasRole('super_admin') || $this->hasRole('admin') || $this->hasRole('host');
    }

    /**
     * Get all apartments created by the user.
     */
    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    /**
     * Get all reservations made by the user.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get all reviews made by the user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
