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
        Schema::table('apartments', function (Blueprint $table) {
            $table->integer('min_nights')->default(1)->after('price_per_night');
            $table->integer('discount_nights')->nullable()->after('min_nights');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_nights');
            $table->json('blocked_dates')->nullable()->after('discount_percentage');
            $table->json('custom_pricing')->nullable()->after('blocked_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn([
                'min_nights',
                'discount_nights',
                'discount_percentage',
                'blocked_dates',
                'custom_pricing'
            ]);
        });
    }
};
