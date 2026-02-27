<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Individual indexes
            $table->index('apartment_id');
            $table->index('status');
            $table->index('date_from');
            $table->index('date_to');
            $table->index('user_id');
            
            // Composite index for availability queries (most important!)
            $table->index(['apartment_id', 'status', 'date_from', 'date_to'], 'idx_reservations_availability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['apartment_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['date_from']);
            $table->dropIndex(['date_to']);
            $table->dropIndex(['user_id']);
            $table->dropIndex('idx_reservations_availability');
        });
    }
};
