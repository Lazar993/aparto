<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Models\Review;
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

        if ($this->hasAlreadyReviewed($reservation)) {
            return (new MailMessage)
                ->subject(__('notifications.review_reminder.subject'))
                ->greeting(__('notifications.greeting', ['name' => $reservation->name]))
                ->line(__('notifications.review_reminder.intro'))
                ->line(__('notifications.review_reminder.already_reviewed', ['apartment' => $apartmentTitle]))
                ->line(__('notifications.review_reminder.already_reviewed_outro'));
        }

        $reviewUrl = route('apartments.review.entry', ['apartment' => $reservation->apartment_id]);

        return (new MailMessage)
            ->subject(__('notifications.review_reminder.subject'))
            ->greeting(__('notifications.greeting', ['name' => $reservation->name]))
            ->line(__('notifications.review_reminder.intro'))
            ->line(__('notifications.review_reminder.stay_ended', ['apartment' => $apartmentTitle]))
            ->line(__('notifications.review_reminder.impact'))
            ->action(__('notifications.review_reminder.action'), $reviewUrl)
            ->line(__('notifications.review_reminder.outro'));
    }

    private function hasAlreadyReviewed(Reservation $reservation): bool
    {
        return Review::query()
            ->where('apartment_id', $reservation->apartment_id)
            ->where(function ($query) use ($reservation) {
                if ($reservation->user_id) {
                    $query->where('user_id', $reservation->user_id);
                }

                if (! empty($reservation->email)) {
                    $emailCondition = $reservation->user_id ? 'orWhereHas' : 'whereHas';

                    $query->{$emailCondition}('user', function ($userQuery) use ($reservation) {
                        $userQuery->where('email', $reservation->email);
                    });
                }
            })
            ->exists();
    }
}
