<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReviewReminder extends Notification implements ShouldQueue
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
        $reviewUrl = route('apartments.show', ['id' => $reservation->apartment_id]);

        return (new MailMessage)
            ->subject(__('notifications.review_reminder.subject'))
            ->greeting(__('notifications.greeting', ['name' => $reservation->name]))
            ->line(__('notifications.review_reminder.intro'))
            ->line(__('notifications.review_reminder.stay_ended', ['apartment' => $apartmentTitle]))
            ->line(__('notifications.review_reminder.impact'))
            ->action(__('notifications.review_reminder.action'), $reviewUrl)
            ->line(__('notifications.review_reminder.outro'));
    }
}
