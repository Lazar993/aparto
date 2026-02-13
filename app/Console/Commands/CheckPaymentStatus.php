<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up pending reservations older than 30 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Reservation::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->delete();

        return Command::SUCCESS;
    }
}
