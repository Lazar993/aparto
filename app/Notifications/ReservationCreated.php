<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCreated extends Notification
{
    use Queueable;

    protected $reservation;
    protected $isNewUser;

    public function __construct(Reservation $reservation, bool $isNewUser = false)
    {
        $this->reservation = $reservation;
        $this->isNewUser = $isNewUser;
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
            ->subject('Reservation Confirmation - ' . $apartment->title)
            ->greeting('Hello ' . $reservation->name . '!')
            ->line('Thank you for your reservation request.')
            ->line('**Apartment:** ' . $apartment->title)
            ->line('**Check-in:** ' . \Carbon\Carbon::parse($reservation->date_from)->format('F j, Y'))
            ->line('**Check-out:** ' . \Carbon\Carbon::parse($reservation->date_to)->format('F j, Y'))
            ->line('**Nights:** ' . $reservation->nights)
            ->line('**Total Price:** $' . number_format($reservation->total_price, 2))
            ->line('**Deposit Amount:** $' . number_format($reservation->deposit_amount, 2))
            ->line('Your reservation is currently pending confirmation. We will review it and get back to you shortly.');

        if ($this->isNewUser) {
            $message->line('')
                ->line('**Account Created**')
                ->line('An account has been created for you. You will receive a separate email to set your password.')
                ->action('Set Your Password', url('/forgot-password'));
        }

        $message->line('If you have any questions, please contact us.')
            ->salutation('Best regards, ' . config('app.name'));

        return $message;
    }
}
