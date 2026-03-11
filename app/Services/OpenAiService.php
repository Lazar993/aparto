<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class OpenAiService
{
    public function generateApartmentDescription(array $data): ?string
    {
        try {
            $title = trim((string) ($data['title'] ?? ''));
            $city = trim((string) ($data['city'] ?? ''));
            $address = trim((string) ($data['address'] ?? ''));
            $rooms = (int) ($data['rooms'] ?? 0);
            $guestNumber = (int) ($data['guest_number'] ?? 0);
            $pricePerNight = $data['price_per_night'] ?? null;


            $features = array_filter([
                !empty($data['parking']) ? 'parking available' : null,
                !empty($data['wifi']) ? 'WiFi included' : null,
                !empty($data['pet_friendly']) ? 'pet friendly' : null,
            ]);

            $parts = array_filter([
                $title !== '' ? "Title: {$title}." : null,
                $city !== '' ? "City: {$city}." : null,
                $address !== '' ? "Address: {$address}." : null,
                $rooms > 0 ? "Rooms: {$rooms}." : null,
                $guestNumber > 0 ? "Guests: {$guestNumber}." : null,
                $pricePerNight !== null ? 'Price per night: ' . $pricePerNight . '.' : null,
                $features ? 'Features: ' . implode(', ', $features) . '.' : null,
            ]);

            $prompt = "Napišite kratak i prijatan opis stana za sajt za rezervacije. Koristite 2 do 4 rečenice (60–120 reči). Koristite isključivo sledeće podatke i ne izmišljajte činjenice. U prvoj rečenici pomenite grad i tip smeštaja.\n\n" . implode("\n", $parts);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Pišete jasne, prijateljske opise stanova za iznajmljivanje.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 200,
            ]);

            $description = trim((string) ($response->choices[0]->message->content ?? ''));

            return $description !== '' ? $description : null;
        } catch (Throwable $e) {
            logger()->warning('OpenAI description generation failed.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function parseReservationIntent(string $message): array
    {
        $today = now();
        $todayDate = $today->toDateString();
        $currentYear = $today->year;
        $nextYear = $today->copy()->addYear()->year;
        $guestNumber = $this->extractGuestNumber($message);
        $locale = app()->getLocale();

        $languageContext = $this->intentLanguageContext($locale, $todayDate, $currentYear, $nextYear);
        $system = $languageContext['system'];
        $intro = $languageContext['intro'];
        $rules = $languageContext['rules'];
        $guestHint = $languageContext['guest_hint'];
        $messageLabel = $languageContext['message_label'];

        $prompt = <<<PROMPT
            {$intro}
            {$rules}

            {$guestHint}: {$guestNumber}

            {$messageLabel}:
            "$message"
            PROMPT; 

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
            'max_tokens' => 150,
        ]);

        $content = $response->choices[0]->message->content ?? '';

        logger()->info('AI RAW RESPONSE', ['raw' => $content]);

        // Remove ```json ... ```
        $clean = preg_replace('/```(json)?/i', '', $content);
        $clean = trim($clean);

        $data = json_decode($clean, true);

        return is_array($data) ? $data : [];
    }

    private function extractGuestNumber(string $message): ?int
    {
        if (preg_match('/\b(\d+)\s*(guests?|people|persons|gosta|gostiju|osoba|ljudi|гостей|гостя|гость|человек|человека|персон|людей)\b/iu', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function intentLanguageContext(string $locale, string $todayDate, int $currentYear, int $nextYear): array
    {
        return match ($locale) {
            'en' => [
                'system' => 'You extract structured apartment reservation data.',
                'intro' => 'Extract reservation information from the message below. Return only JSON with keys: city, date_from, date_to, max_price, guests.',
                'rules' => "Date format must be YYYY-MM-DD. If a value is missing, return null.\nToday is {$todayDate}. If user does not specify a year (e.g. \"9.3\"), use {$currentYear}. If that date is before today, use {$nextYear}.\nDo not return any explanation, only JSON.",
                'guest_hint' => 'Detected guest count (if present in message, otherwise null)',
                'message_label' => 'Message',
            ],
            'ru' => [
                'system' => 'Ты извлекаешь структурированные данные для бронирования апартаментов.',
                'intro' => 'Извлеки данные бронирования из сообщения ниже. Верни только JSON с ключами: city, date_from, date_to, max_price, guests.',
                'rules' => "Формат даты: YYYY-MM-DD. Если значение отсутствует, верни null.\nСегодня {$todayDate}. Если пользователь не указал год (например, \"9.3\"), используй {$currentYear}. Если такая дата уже прошла, используй {$nextYear}.\nНе добавляй объяснений, только JSON.",
                'guest_hint' => 'Количество гостей (если указано в сообщении, иначе null)',
                'message_label' => 'Сообщение',
            ],
            default => [
                'system' => 'Izvlačiš strukturirane podatke za rezervaciju apartmana.',
                'intro' => 'Izvlači podatke o rezervaciji iz sledeće poruke. Vrati samo JSON sa ključevima: city, date_from, date_to, max_price, guests.',
                'rules' => "Datum mora biti u formatu YYYY-MM-DD. Ako nešto nedostaje, vrati null.\nDanašnji datum je {$todayDate}. Ako korisnik ne navede godinu (npr. \"9.3\"), koristi {$currentYear}. Ako je taj datum pre današnjeg datuma, koristi {$nextYear}.\nNe vraćaj objašnjenja, samo JSON.",
                'guest_hint' => 'Broj gostiju (ako je naveden u poruci, inače null)',
                'message_label' => 'Poruka',
            ],
        };
    }


}
