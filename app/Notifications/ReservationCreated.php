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

        if ($this->isNewUser && $this->passwordResetToken) {
            $resetUrl = url(route('password.reset', [
                'token' => $this->passwordResetToken,
                'email' => $reservation->email,
            ], false));
            
            $message->line('')
                ->line('**Welcome! Your Account Has Been Created**')
                ->line('To access your account and view your reservations, please set your password by clicking the button below:')
                ->action('Set Your Password', $resetUrl)
                ->line('This link will expire in 60 minutes.');
        } elseif ($this->isNewUser) {
            // Fallback if no token (shouldn't happen)
            $message->line('')
                ->line('**Account Created**')
                ->line('An account has been created for you. Please use the "Forgot Password" option on the login page to set your password.');
        }

        $message->line('If you have any questions, please contact us.')
            ->salutation('Best regards, ' . config('app.name'));

        return $message;
    }
}
