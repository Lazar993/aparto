<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationCreated;
use App\Notifications\ReservationConfirmed;
use App\Notifications\ReservationCanceled;
use Illuminate\Support\Facades\Log;

class ReservationObserver
{
    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        Log::info('ReservationObserver: created event', [
            'reservation_id' => $reservation->id,
            'status' => $reservation->status,
            'user_id' => $reservation->user_id,
        ]);

        // Send reservation created notification
        try {
            if ($reservation->user_id) {
                $user = User::find($reservation->user_id);
                if ($user) {
                    // Check if this is a new user (created recently, within last minute)
                    $isNewUser = $user->created_at && $user->created_at->gt(now()->subMinute());
                    
                    // Generate password reset token for new users
                    $passwordResetToken = null;
                    if ($isNewUser) {
                        $passwordResetToken = app('auth.password.broker')->createToken($user);
                    }
                    
                    $user->notify(new ReservationCreated($reservation, $isNewUser, $passwordResetToken));
                    
                    Log::info('ReservationCreated email sent', [
                        'reservation_id' => $reservation->id,
                        'user_email' => $user->email,
                        'is_new_user' => $isNewUser,
                    ]);
                }
            } else {
                // Guest reservation (no user account)
                \Illuminate\Support\Facades\Notification::route('mail', $reservation->email)
                    ->notify(new ReservationCreated($reservation, false, null));
                    
                Log::info('ReservationCreated email sent to guest', [
                    'reservation_id' => $reservation->id,
                    'email' => $reservation->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ReservationCreated email', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);
        }
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        // Check if status was changed
        if ($reservation->isDirty('status')) {
            $oldStatus = $reservation->getOriginal('status');
            $newStatus = $reservation->status;
            
            Log::info('ReservationObserver: status changed', [
                'reservation_id' => $reservation->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Don't send status change emails if going from null/pending to pending
            // (this happens on initial creation)
            if ($oldStatus === $newStatus || (empty($oldStatus) && $newStatus === 'pending')) {
                return;
            }

            try {
                $notification = null;
                
                switch ($newStatus) {
                    case 'confirmed':
                        $notification = new ReservationConfirmed($reservation);
                        $emailType = 'confirmed';
                        break;
                    case 'canceled':
                        $notification = new ReservationCanceled($reservation);
                        $emailType = 'canceled';
                        break;
                }

                if ($notification) {
                    if ($reservation->user_id) {
                        $user = User::find($reservation->user_id);
                        if ($user) {
                            $user->notify($notification);
                            Log::info("Reservation{$emailType} email sent", [
                                'reservation_id' => $reservation->id,
                                'user_email' => $user->email,
                                'trigger' => 'observer',
                            ]);
                        }
                    } else {
                        // Guest reservation
                        \Illuminate\Support\Facades\Notification::route('mail', $reservation->email)
                            ->notify($notification);
                            
                        Log::info("Reservation{$emailType} email sent to guest", [
                            'reservation_id' => $reservation->id,
                            'email' => $reservation->email,
                            'trigger' => 'observer',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send status change email', [
                    'error' => $e->getMessage(),
                    'reservation_id' => $reservation->id,
                    'new_status' => $newStatus,
                ]);
            }
        }
    }
}
