<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCanceled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $reservation = $this->reservation;
        $apartment = $reservation->apartment;
        $checkIn = \Carbon\Carbon::parse($reservation->date_from)->translatedFormat('F j, Y');
        $checkOut = \Carbon\Carbon::parse($reservation->date_to)->translatedFormat('F j, Y');
        
        $message = (new MailMessage)
            ->subject(__('notifications.reservation_canceled.subject', ['apartment' => $apartment->title]))
            ->greeting(__('notifications.greeting_comma', ['name' => $reservation->name]))
            ->line(__('notifications.reservation_canceled.intro'))
            ->line('**' . __('notifications.fields.apartment') . ':** ' . $apartment->title)
            ->line('**' . __('notifications.fields.check_in') . ':** ' . $checkIn)
            ->line('**' . __('notifications.fields.check_out') . ':** ' . $checkOut)
            ->line(__('notifications.reservation_canceled.outro'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));

        return $message;
    }
}
