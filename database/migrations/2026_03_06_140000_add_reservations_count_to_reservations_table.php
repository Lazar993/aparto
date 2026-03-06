<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('reservations_count')->after('note')->default(0);
            $table->index('reservations_count');
        });

        $countsByApartment = DB::table('reservations')
            ->select('apartment_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'confirmed')
            ->whereNull('deleted_at')
            ->groupBy('apartment_id')
            ->get();

        foreach ($countsByApartment as $row) {
            DB::table('reservations')
                ->where('apartment_id', $row->apartment_id)
                ->update(['reservations_count' => (int) $row->total]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['reservations_count']);
            $table->dropColumn('reservations_count');
        });
    }
};
