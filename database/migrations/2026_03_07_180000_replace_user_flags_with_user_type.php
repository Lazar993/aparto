<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['admin', 'host', 'front'])
                    ->default('front')
                    ->after('password');
                $table->index('user_type');
            }
        });

        $hasIsAdmin = Schema::hasColumn('users', 'is_admin');
        $hasFrontUser = Schema::hasColumn('users', 'front_user');

        if ($hasIsAdmin && $hasFrontUser) {
            DB::table('users')
                ->where('is_admin', true)
                ->update(['user_type' => 'admin']);

            DB::table('users')
                ->where('is_admin', false)
                ->where('front_user', true)
                ->update(['user_type' => 'front']);

            DB::table('users')
                ->where('is_admin', false)
                ->where(function ($query): void {
                    $query->where('front_user', false)
                        ->orWhereNull('front_user');
                })
                ->update(['user_type' => 'host']);
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'front_user')) {
                $table->dropColumn('front_user');
            }

            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false);
            }

            if (!Schema::hasColumn('users', 'front_user')) {
                $table->boolean('front_user')->default(false);
            }
        });

        if (Schema::hasColumn('users', 'user_type')) {
            DB::table('users')
                ->where('user_type', 'admin')
                ->update(['is_admin' => true, 'front_user' => false]);

            DB::table('users')
                ->where('user_type', 'front')
                ->update(['is_admin' => false, 'front_user' => true]);

            DB::table('users')
                ->where('user_type', 'host')
                ->update(['is_admin' => false, 'front_user' => false]);
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropIndex(['user_type']);
                $table->dropColumn('user_type');
            }
        });
    }
};
