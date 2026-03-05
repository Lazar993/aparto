<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartment_blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->timestamps();

            $table->index(['apartment_id', 'date_from', 'date_to'], 'apartment_blocked_periods_dates_idx');
        });

        DB::table('apartments')
            ->select(['id', 'blocked_dates'])
            ->whereNotNull('blocked_dates')
            ->orderBy('id')
            ->chunkById(200, function ($apartments): void {
                $rows = [];
                $now = now();

                foreach ($apartments as $apartment) {
                    $blockedDates = is_array($apartment->blocked_dates)
                        ? $apartment->blocked_dates
                        : json_decode((string) $apartment->blocked_dates, true);

                    if (!is_array($blockedDates)) {
                        continue;
                    }

                    foreach ($blockedDates as $blocked) {
                        if (!is_array($blocked)) {
                            continue;
                        }

                        $from = $blocked['from'] ?? $blocked['date_from'] ?? null;
                        $to = $blocked['to'] ?? $blocked['date_to'] ?? null;

                        if (!$from || !$to) {
                            continue;
                        }

                        try {
                            $fromDate = \Carbon\Carbon::parse($from)->toDateString();
                            $toDate = \Carbon\Carbon::parse($to)->toDateString();
                        } catch (\Throwable $e) {
                            continue;
                        }

                        if ($toDate < $fromDate) {
                            continue;
                        }

                        $rows[] = [
                            'apartment_id' => $apartment->id,
                            'date_from' => $fromDate,
                            'date_to' => $toDate,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($rows)) {
                    DB::table('apartment_blocked_periods')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_blocked_periods');
    }
};
