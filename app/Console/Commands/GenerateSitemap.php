<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';

    protected $description = 'Generate the sitemap.xml file';

    private const LOCALES = ['sr', 'en', 'ru'];

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $urls = [];

        // Static pages
        foreach (self::LOCALES as $locale) {
            $urls[] = $this->buildUrl("{$baseUrl}/{$locale}", now()->toDateString(), 'daily', '1.0');
            $urls[] = $this->buildUrl("{$baseUrl}/{$locale}/apartments", now()->toDateString(), 'daily', '0.8');
            $urls[] = $this->buildUrl("{$baseUrl}/{$locale}/contact", now()->toDateString(), 'monthly', '0.5');
            $urls[] = $this->buildUrl("{$baseUrl}/{$locale}/become-a-host", now()->toDateString(), 'monthly', '0.5');
        }

        // Active apartments
        $apartments = Apartment::where('active', true)->get();
        foreach ($apartments as $apartment) {
            foreach (self::LOCALES as $locale) {
                $slug = Str::slug($apartment->title);
                $urls[] = $this->buildUrl(
                    "{$baseUrl}/{$locale}/apartments/{$apartment->id}/{$slug}",
                    $apartment->updated_at->toDateString(),
                    'daily',
                    '0.9'
                );
            }
        }

        // Hosts
        $hosts = User::where('user_type', User::TYPE_HOST)->get();
        foreach ($hosts as $host) {
            foreach (self::LOCALES as $locale) {
                $slug = Str::slug($host->name);
                $urls[] = $this->buildUrl(
                    "{$baseUrl}/{$locale}/host/{$host->id}/{$slug}",
                    $host->updated_at->toDateString(),
                    'weekly',
                    '0.7'
                );
            }
        }

        // Dynamic pages
        $pages = Page::where('is_active', true)->get();
        foreach ($pages as $page) {
            foreach (self::LOCALES as $locale) {
                $urls[] = $this->buildUrl(
                    "{$baseUrl}/{$locale}/pages/{$page->slug}",
                    $page->updated_at->toDateString(),
                    'weekly',
                    '0.6'
                );
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= "    <loc>{$url['loc']}</loc>" . PHP_EOL;
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>" . PHP_EOL;
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>" . PHP_EOL;
            $xml .= "    <priority>{$url['priority']}</priority>" . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }
        $xml .= '</urlset>' . PHP_EOL;

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap generated with ' . count($urls) . ' URLs.');

        return self::SUCCESS;
    }

    private function buildUrl(string $loc, string $lastmod, string $changefreq, string $priority): array
    {
        return compact('loc', 'lastmod', 'changefreq', 'priority');
    }
}
