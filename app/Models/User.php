<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use App\Models\Review;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public const TYPE_ADMIN = 'admin';
    public const TYPE_HOST = 'host';
    public const TYPE_FRONT = 'front';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'password',
        'user_type',
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
        'user_type' => 'string',
    ];

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    public static function userTypeOptions(): array
    {
        return [
            self::TYPE_ADMIN => 'Admin',
            self::TYPE_HOST => 'Host',
            self::TYPE_FRONT => 'Front',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->user_type === self::TYPE_ADMIN;
    }

    public function isHost(): bool
    {
        return $this->user_type === self::TYPE_HOST;
    }

    public function isFrontUser(): bool
    {
        return $this->user_type === self::TYPE_FRONT;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($this->isAdmin() || $this->isHost()) {
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

    /**
     * Get all wishlist entries created by the user.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get all apartments saved in user's wishlist.
     */
    public function wishlistApartments()
    {
        return $this->belongsToMany(Apartment::class, 'wishlists')
            ->withPivot('created_at');
    }
}
