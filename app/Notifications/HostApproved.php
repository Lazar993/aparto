<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HostApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $passwordResetToken;

    public function __construct(string $passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->passwordResetToken,
            'email' => $notifiable->email,
        ], false));

        return (new MailMessage)
            ->subject(__('notifications.host_approved.subject', ['app' => config('app.name')]))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.host_approved.intro', ['app' => config('app.name')]))
            ->line(__('notifications.host_approved.account_ready'))
            ->action(__('notifications.host_approved.set_password_action'), $resetUrl)
            ->line(__('notifications.host_approved.set_password_expiry'))
            ->line(__('notifications.host_approved.outro'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));
    }
}
