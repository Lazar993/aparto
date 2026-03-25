<?php

namespace App\Http\Repository;

use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReservationRepository
{
    public function getUserReservations(User $user, int $perPage): LengthAwarePaginator
    {
        return Reservation::query()
            ->with('apartment')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($emailQuery) use ($user) {
                        $emailQuery->whereNull('user_id')
                            ->where('email', $user->email);
                    });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function lockApartmentForReservation(int $apartmentId): Apartment
    {
        return Apartment::query()
            ->whereKey($apartmentId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function userHasPastReservation(int $apartmentId, int $userId, string $userEmail): bool
    {
        return Reservation::where('apartment_id', $apartmentId)
            ->where(function ($query) use ($userId, $userEmail) {
                $query->where('user_id', $userId)
                    ->orWhere('email', $userEmail);
            })
            ->where('date_to', '<', now())
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();
    }
}
