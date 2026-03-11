<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\ReservationReviewReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendReservationReviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-review-reminders {--dry-run : Show eligible reservations without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send review reminder emails after completed confirmed stays';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Reservation::query()
            ->where('status', 'confirmed')
            ->whereDate('date_to', '<=', now()->toDateString())
            ->whereNull('review_reminder_sent_at')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No reservations are eligible for review reminders.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} reservations eligible for review reminders.");

        $sent = 0;
        $failed = 0;

        $query->chunkById(100, function ($reservations) use (&$sent, &$failed, $dryRun): void {
            foreach ($reservations as $reservation) {
                if ($dryRun) {
                    $this->line("[dry-run] Reservation #{$reservation->id} ({$reservation->email})");
                    continue;
                }

                try {
                    $notification = (new ReservationReviewReminder($reservation))
                        ->locale($this->resolveReservationLocale($reservation));

                    if ($reservation->user_id && $reservation->user) {
                        $reservation->user->notify($notification);
                    } elseif (! empty($reservation->email)) {
                        Notification::route('mail', $reservation->email)->notify($notification);
                    } else {
                        throw new \RuntimeException('Reservation has no user and no email.');
                    }

                    $reservation->forceFill([
                        'review_reminder_sent_at' => now(),
                    ])->saveQuietly();

                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('Failed to send reservation review reminder', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);

                    $this->error("Failed reservation #{$reservation->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Review reminder run completed. Sent: {$sent}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveReservationLocale(Reservation $reservation): string
    {
        $allowed = ['en', 'sr', 'ru'];
        $fallback = (string) config('app.locale', 'en');
        $locale = (string) ($reservation->locale ?? $fallback);

        if (! in_array($locale, $allowed, true)) {
            return $fallback;
        }

        return $locale;
    }
}
