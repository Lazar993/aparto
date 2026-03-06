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
            ->subject('How Was Your Stay? Leave a Review')
            ->greeting('Hello ' . $reservation->name . '!')
            ->line('Thank you for staying with us.')
            ->line('Your stay at **' . $apartmentTitle . '** has ended, and we would love to hear your feedback.')
            ->line('Your review helps other guests make better booking decisions.')
            ->action('Leave a Review', $reviewUrl)
            ->line('Thank you for your time and support.');
    }
}
