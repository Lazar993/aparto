<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    public const TRANSLATABLE_LOCALES = ['sr', 'en', 'ru'];

    protected $fillable = [
        'title',
        'title_i18n',
        'slug',
        'content',
        'content_i18n',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'title_i18n' => 'array',
        'content_i18n' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            $titleTranslations = self::normalizeTranslations($page->title_i18n);
            $contentTranslations = self::normalizeTranslations($page->content_i18n);

            $page->title_i18n = $titleTranslations;
            $page->content_i18n = $contentTranslations;

            // Keep legacy columns in sync for existing queries/search.
            $page->title = self::pickSourceValue($titleTranslations, (string) $page->getRawOriginal('title', ''));
            $page->content = self::pickSourceValue($contentTranslations, (string) $page->getRawOriginal('content', ''));
        });
    }

    public function getTitleAttribute(?string $value): string
    {
        return $this->resolveLocalizedValue($this->title_i18n, $value);
    }

    public function getContentAttribute(?string $value): string
    {
        return $this->resolveLocalizedValue($this->content_i18n, $value);
    }

    private static function normalizeTranslations(mixed $translations): array
    {
        $translations = is_array($translations) ? $translations : [];

        $normalized = [];

        foreach (self::TRANSLATABLE_LOCALES as $locale) {
            $value = Arr::get($translations, $locale);
            $value = is_string($value) ? trim($value) : '';

            $normalized[$locale] = $value !== '' ? $value : null;
        }

        return $normalized;
    }

    private static function pickSourceValue(array $translations, string $legacy): string
    {
        $fallbackLocale = (string) config('app.fallback_locale', 'sr');
        $order = array_unique(['sr', $fallbackLocale, 'en', 'ru']);

        foreach ($order as $locale) {
            $value = Arr::get($translations, $locale);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $legacy;
    }

    private function resolveLocalizedValue(mixed $translations, ?string $legacyValue): string
    {
        $translations = is_array($translations) ? $translations : [];
        $locale = app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'sr');
        $order = array_unique([$locale, 'sr', $fallbackLocale, 'en', 'ru']);

        foreach ($order as $requestedLocale) {
            $value = Arr::get($translations, $requestedLocale);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return (string) ($legacyValue ?? '');
    }
}
