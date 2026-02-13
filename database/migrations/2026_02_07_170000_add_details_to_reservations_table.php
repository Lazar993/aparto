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
            $table->unsignedInteger('nights')
                ->after('date_to');
            $table->decimal('price_per_night', 10, 2)
                ->after('nights');
            $table->string('payment_provider')
                ->nullable()
                ->after('status');
            $table->string('payment_reference')
                ->nullable()
                ->after('payment_provider');
            $table->timestamp('paid_at')
                ->nullable()
                ->after('payment_reference');
            $table->text('note')
                ->nullable()
                ->after('paid_at');

            $table->index(['apartment_id', 'date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['apartment_id', 'date_from', 'date_to']);

            $table->dropColumn([
                'nights',
                'price_per_night',
                'payment_provider',
                'payment_reference',
                'paid_at',
                'note',
            ]);
        });
    }
};
