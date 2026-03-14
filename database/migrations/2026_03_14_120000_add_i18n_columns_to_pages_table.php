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
        Schema::table('pages', function (Blueprint $table) {
            $table->json('title_i18n')->nullable()->after('title');
            $table->json('content_i18n')->nullable()->after('content');
        });

        DB::table('pages')
            ->select(['id', 'title', 'content'])
            ->orderBy('id')
            ->chunkById(100, function ($pages): void {
                foreach ($pages as $page) {
                    DB::table('pages')
                        ->where('id', $page->id)
                        ->update([
                            'title_i18n' => json_encode([
                                'sr' => $page->title,
                                'en' => null,
                                'ru' => null,
                            ], JSON_UNESCAPED_UNICODE),
                            'content_i18n' => json_encode([
                                'sr' => $page->content,
                                'en' => null,
                                'ru' => null,
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['title_i18n', 'content_i18n']);
        });
    }
};
