<?php

namespace App\Notifications;

use App\Models\HostRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HostRequestCreatedForAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    protected HostRequest $hostRequest;

    public function __construct(HostRequest $hostRequest)
    {
        $this->hostRequest = $hostRequest;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->hostRequest;

        return (new MailMessage)
            ->subject(__('notifications.host_request_admin.subject', ['name' => $r->name]))
            ->greeting(__('notifications.host_request_admin.greeting'))
            ->line(__('notifications.host_request_admin.intro'))
            ->line('**' . __('notifications.host_request_admin.fields.name') . ':** ' . $r->name)
            ->line('**' . __('notifications.host_request_admin.fields.email') . ':** ' . $r->email)
            ->line('**' . __('notifications.host_request_admin.fields.phone') . ':** ' . $r->phone)
            ->line('**' . __('notifications.host_request_admin.fields.city') . ':** ' . $r->city)
            ->line('**' . __('notifications.host_request_admin.fields.listing_url') . ':** ' . ($r->listing_url ?: '-'))
            ->line('**' . __('notifications.host_request_admin.fields.apartments') . ':** ' . ($r->number_of_apartments ?: '-'))
            ->action(__('notifications.host_request_admin.action'), url('/admin/host-requests'))
            ->line(__('notifications.host_request_admin.outro'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));
    }
}
