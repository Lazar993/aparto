<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;
    protected $isNewUser;
    protected $passwordResetToken;

    public function __construct(Reservation $reservation, bool $isNewUser = false, ?string $passwordResetToken = null)
    {
        $this->reservation = $reservation;
        $this->isNewUser = $isNewUser;
        $this->passwordResetToken = $passwordResetToken;
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
            ->subject(__('notifications.reservation_created.subject', ['apartment' => $apartment->title]))
            ->greeting(__('notifications.greeting', ['name' => $reservation->name]))
            ->line(__('notifications.reservation_created.intro'))
            ->line('**' . __('notifications.fields.apartment') . ':** ' . $apartment->title)
            ->line('**' . __('notifications.fields.check_in') . ':** ' . $checkIn)
            ->line('**' . __('notifications.fields.check_out') . ':** ' . $checkOut)
            ->line('**' . __('notifications.fields.nights') . ':** ' . $reservation->nights)
            ->line('**' . __('notifications.fields.total_price') . ':** $' . number_format($reservation->total_price, 2))
            ->line('**' . __('notifications.fields.deposit_amount') . ':** $' . number_format($reservation->deposit_amount, 2))
            ->line(__('notifications.reservation_created.pending_notice'));

        if ($this->isNewUser && $this->passwordResetToken) {
            $resetUrl = url(route('password.reset', [
                'token' => $this->passwordResetToken,
                'email' => $reservation->email,
            ], false));
            
            $message->line('')
                ->line('**' . __('notifications.reservation_created.account_created_title') . '**')
                ->line(__('notifications.reservation_created.account_created_intro'))
                ->action(__('notifications.reservation_created.set_password_action'), $resetUrl)
                ->line(__('notifications.reservation_created.set_password_expiry'));
        } elseif ($this->isNewUser) {
            // Fallback if no token (shouldn't happen)
            $message->line('')
                ->line('**' . __('notifications.reservation_created.account_created_fallback_title') . '**')
                ->line(__('notifications.reservation_created.account_created_fallback_body'));
        }

        $message->line(__('notifications.reservation_created.outro'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));

        return $message;
    }
}
