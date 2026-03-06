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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('front_user')->default(false);
        });

        // Backfill existing users who already created reservations.
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')
                    ->from('reservations')
                    ->whereNotNull('user_id')
                    ->distinct();
            })
            ->update(['front_user' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('front_user');
        });
    }
};
