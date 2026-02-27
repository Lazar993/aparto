<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmed extends Notification implements ShouldQueue
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
            ->subject('Reservation Confirmed - ' . $apartment->title)
            ->greeting('Hello ' . $reservation->name . '!')
            ->line('Great news! Your reservation has been confirmed.')
            ->line('**Apartment:** ' . $apartment->title)
            ->line('**Check-in:** ' . \Carbon\Carbon::parse($reservation->date_from)->format('F j, Y'))
            ->line('**Check-out:** ' . \Carbon\Carbon::parse($reservation->date_to)->format('F j, Y'))
            ->line('**Nights:** ' . $reservation->nights)
            ->line('**Total Price:** $' . number_format($reservation->total_price, 2))
            ->line('**Deposit Amount:** $' . number_format($reservation->deposit_amount, 2))
            ->line('**Balance Due:** $' . number_format(max(0, $reservation->total_price - ($reservation->deposit_amount ?? 0)), 2));

        if ($apartment->address) {
            $message->line('')
                ->line('**Address:** ' . $apartment->address);
        }

        $message->line('')
            ->line('We look forward to hosting you!')
            ->line('If you have any questions or special requests, please don\'t hesitate to contact us.')
            ->salutation('Best regards, ' . config('app.name'));

        return $message;
    }
}
