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
        
        $message = (new MailMessage)
            ->subject('Reservation Canceled - ' . $apartment->title)
            ->greeting('Hello ' . $reservation->name . ',')
            ->line('Your reservation has been canceled.')
            ->line('**Apartment:** ' . $apartment->title)
            ->line('**Check-in:** ' . \Carbon\Carbon::parse($reservation->date_from)->format('F j, Y'))
            ->line('**Check-out:** ' . \Carbon\Carbon::parse($reservation->date_to)->format('F j, Y'))
            ->line('If you did not request this cancellation or have any questions, please contact us immediately.')
            ->salutation('Best regards, ' . config('app.name'));

        return $message;
    }
}
