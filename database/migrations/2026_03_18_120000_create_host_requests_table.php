<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 160)->index();
            $table->string('phone', 30);
            $table->string('city', 100);
            $table->string('listing_url', 500)->nullable();
            $table->unsignedSmallInteger('number_of_apartments')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_requests');
    }
};
