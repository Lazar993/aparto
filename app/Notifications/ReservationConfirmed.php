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
        $checkIn = \Carbon\Carbon::parse($reservation->date_from)->translatedFormat('F j, Y');
        $checkOut = \Carbon\Carbon::parse($reservation->date_to)->translatedFormat('F j, Y');
        
        $message = (new MailMessage)
            ->subject(__('notifications.reservation_confirmed.subject', ['apartment' => $apartment->title]))
            ->greeting(__('notifications.greeting', ['name' => $reservation->name]))
            ->line(__('notifications.reservation_confirmed.intro'))
            ->line('**' . __('notifications.fields.apartment') . ':** ' . $apartment->title)
            ->line('**' . __('notifications.fields.check_in') . ':** ' . $checkIn)
            ->line('**' . __('notifications.fields.check_out') . ':** ' . $checkOut)
            ->line('**' . __('notifications.fields.nights') . ':** ' . $reservation->nights)
            ->line('**' . __('notifications.fields.total_price') . ':** $' . number_format($reservation->total_price, 2))
            ->line('**' . __('notifications.fields.deposit_amount') . ':** $' . number_format($reservation->deposit_amount, 2))
            ->line('**' . __('notifications.fields.balance_due') . ':** $' . number_format(max(0, $reservation->total_price - ($reservation->deposit_amount ?? 0)), 2));

        if ($apartment->address) {
            $message->line('')
                ->line('**' . __('notifications.fields.address') . ':** ' . $apartment->address);
        }

        $message->line('')
            ->line(__('notifications.reservation_confirmed.outro_one'))
            ->line(__('notifications.reservation_confirmed.outro_two'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));

        return $message;
    }
}
