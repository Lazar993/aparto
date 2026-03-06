<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ReservationCreatedForHost extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Reservation $reservation)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $reservation->loadMissing('apartment');
        $apartment = $reservation->apartment;

        $apartmentTitle = $apartment?->title ?? ('Apartment #' . $reservation->apartment_id);

        $adminReservationUrl = $this->resolveAdminReservationUrl($notifiable, $reservation);

        return (new MailMessage)
            ->subject('New Reservation Request - ' . $apartmentTitle)
            ->greeting('Hello ' . ($notifiable->name ?? 'Host') . '!')
            ->line('A new reservation request has been submitted for your apartment.')
            ->line('**Apartment:** ' . $apartmentTitle)
            ->line('**Guest:** ' . $reservation->name . ' (' . $reservation->email . ')')
            ->line('**Check-in:** ' . $reservation->date_from->format('F j, Y'))
            ->line('**Check-out:** ' . $reservation->date_to->format('F j, Y'))
            ->line('**Nights:** ' . $reservation->nights)
            ->line('**Total Price:** $' . number_format((float) $reservation->total_price, 2))
            ->action('Open Reservation in Admin', $adminReservationUrl)
            ->line('Please review this reservation in your admin panel.');
    }

    protected function resolveAdminReservationUrl($notifiable, Reservation $reservation): string
    {
        if ($this->can($notifiable, 'update_reservation')) {
            try {
                return route('filament.admin.resources.reservations.edit', [
                    'record' => $reservation->id,
                ]);
            } catch (\Throwable $e) {
                return url('/admin/reservations/' . $reservation->id . '/edit');
            }
        }

        if ($this->can($notifiable, 'view_any_reservation')) {
            try {
                return route('filament.admin.resources.reservations.index');
            } catch (\Throwable $e) {
                return url('/admin/reservations');
            }
        }

        return url('/admin');
    }

    protected function can($notifiable, string $permission): bool
    {
        if (! is_object($notifiable)) {
            return false;
        }

        if (method_exists($notifiable, 'hasPermissionTo')) {
            foreach (['admin', 'web'] as $guard) {
                try {
                    if ($notifiable->hasPermissionTo($permission, $guard)) {
                        return true;
                    }
                } catch (PermissionDoesNotExist) {
                    // Permission might only exist for one guard.
                }
            }
        }

        return method_exists($notifiable, 'can') && (bool) $notifiable->can($permission);
    }
}
