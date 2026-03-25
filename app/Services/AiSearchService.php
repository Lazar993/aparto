<?php

namespace App\Services;

use App\Models\Apartment;
use Carbon\Carbon;

class AiSearchService
{
    public function normalizeIntentDates(array $intent, string $message): array
    {
        if (empty($intent['date_from']) || empty($intent['date_to'])) {
            return $intent;
        }

        $hasExplicitYear = preg_match('/\b\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}\b|\b\d{4}[.\/-]\d{1,2}[.\/-]\d{1,2}\b/', $message) === 1;

        if ($hasExplicitYear) {
            return $intent;
        }

        try {
            $parsedFrom = Carbon::parse($intent['date_from']);
            $parsedTo = Carbon::parse($intent['date_to']);
        } catch (\Throwable $e) {
            return $intent;
        }

        $today = Carbon::today();
        $from = Carbon::create($today->year, $parsedFrom->month, $parsedFrom->day)->startOfDay();
        $to = Carbon::create($today->year, $parsedTo->month, $parsedTo->day)->startOfDay();

        if ($to->lt($from)) {
            $to->addYear();
        }

        if ($from->lt($today)) {
            $from->addYear();
            if ($to->lt($from)) {
                $to->addYear();
            }
        }

        $intent['date_from'] = $from->toDateString();
        $intent['date_to'] = $to->toDateString();

        return $intent;
    }

    public function buildCitySearchTerms(string $city): array
    {
        $city = trim($city);

        if ($city === '') {
            return [];
        }

        $primaryCity = trim((string) preg_split('/[,|]/u', $city)[0]);
        $normalized = $this->normalizeCityToken($primaryCity);
        $terms = [
            $city,
            $primaryCity,
            $this->transliterateSerbianCyrillic($city),
            $this->transliterateSerbianCyrillic($primaryCity),
        ];

        $aliases = $this->cityAliasMap();
        if (isset($aliases[$normalized])) {
            foreach ($aliases[$normalized] as $alias) {
                $terms[] = $alias;
            }
        }

        foreach ($aliases as $key => $mappedAliases) {
            if (in_array($normalized, $mappedAliases, true)) {
                $terms[] = $key;
                foreach ($mappedAliases as $alias) {
                    $terms[] = $alias;
                }
            }
        }

        $terms = array_map(static fn (string $value) => trim($value), $terms);
        $terms = array_filter($terms, static fn (string $value) => $value !== '');

        return array_values(array_unique($terms));
    }

    public function filterApartments(array $intent): \Illuminate\Support\Collection
    {
        $cityTerms = $this->buildCitySearchTerms((string) $intent['city']);

        $query = Apartment::where('active', true)
            ->where(function ($q) use ($cityTerms) {
                foreach ($cityTerms as $term) {
                    $q->orWhere('city', 'like', '%' . $term . '%');
                }
            });

        if (! empty($intent['max_price'])) {
            $query->where('price_per_night', '<=', $intent['max_price']);
        }

        if (! empty($intent['guests'])) {
            $query->where('guest_number', '>=', $intent['guests']);
        }

        if (! empty($intent['date_from']) && ! empty($intent['date_to'])) {
            $from = $intent['date_from'];
            $to = $intent['date_to'];

            $query->whereDoesntHave('reservations', function ($q) use ($from, $to) {
                $q->where('status', '!=', 'canceled')
                    ->where('date_from', '<', $to)
                    ->where('date_to', '>', $from);
            });

            $query->whereDoesntHave('blockedPeriods', function ($q) use ($from, $to) {
                $q->where('date_from', '<', $to)
                    ->where('date_to', '>=', $from);
            });
        }

        if (! empty($intent['rooms'])) {
            $query->where('rooms', '>=', $intent['rooms']);
        }

        return $query->get()->take(5)->values();
    }

    private function normalizeCityToken(string $value): string
    {
        $value = mb_strtolower($this->transliterateSerbianCyrillic(trim($value)), 'UTF-8');

        $replace = [
            'š' => 's',
            'đ' => 'd',
            'č' => 'c',
            'ć' => 'c',
            'ž' => 'z',
            '-' => ' ',
        ];

        $value = strtr($value, $replace);
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function transliterateSerbianCyrillic(string $value): string
    {
        return strtr($value, [
            'Љ' => 'Lj', 'Њ' => 'Nj', 'Џ' => 'Dž',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Ђ' => 'Đ',
            'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z', 'И' => 'I', 'Ј' => 'J', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
            'Ц' => 'C', 'Ч' => 'Č', 'Ш' => 'Š',
            'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dž',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ђ' => 'đ',
            'е' => 'e', 'ж' => 'ž', 'з' => 'z', 'и' => 'i', 'ј' => 'j', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'ћ' => 'ć', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
            'ц' => 'c', 'ч' => 'č', 'ш' => 'š',
        ]);
    }

    private function cityAliasMap(): array
    {
        return [
            'beograd' => ['belgrade', 'belgrad', 'beograde', 'beogradu', 'beograda', 'белград', 'белграде', 'белграду', 'белграда'],
            'novi sad' => ['novi sad'],
            'nis' => ['niš'],
            'subotica' => ['subotica'],
            'kragujevac' => [],
            'kraljevo' => [],
            'zlatibor' => [],
            'raska' => ['raška'],
            'sokobanja' => [],
            'vranje' => [],
            'uzice' => ['užice'],
            'kopaonik' => [],
            'silver lake' => ['srebrno jezero'],
        ];
    }
}
