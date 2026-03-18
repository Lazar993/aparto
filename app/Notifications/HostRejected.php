<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HostRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.host_rejected.subject', ['app' => config('app.name')]))
            ->greeting(__('notifications.greeting', ['name' => $this->name]))
            ->line(__('notifications.host_rejected.intro', ['app' => config('app.name')]))
            ->line(__('notifications.host_rejected.rejection_notice'))
            ->line(__('notifications.host_rejected.outro'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));
    }
}
