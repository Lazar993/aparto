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
        $system = 'Izvlačiš strukturirane podatke za rezervaciju apartmana.';
        $today = now();
        $todayDate = $today->toDateString();
        $currentYear = $today->year;
        $nextYear = $today->copy()->addYear()->year;
        $guestNumber = $this->extractGuestNumber($message);

        $prompt = <<<PROMPT
            Izvlači podatke o rezervaciji iz sledeće poruke.
            Vrati samo JSON sa sledećim ključevima: city, date_from, date_to, max_price, guests. 

            Datum mora biti u formatu YYYY-MM-DD. Ako nešto nedostaje, vrati null.
            Današnji datum je {$todayDate}.
            Ako korisnik ne navede godinu (npr. "9.3"), koristi {$currentYear}.
            Ako bi tako dobijeni datum bio pre današnjeg datuma, koristi {$nextYear}.

            Broj gostiju: {$guestNumber} (ako je naveden u poruci, inače null).

            Ne vraćaj nikakva objašnjenja, samo JSON!!!

            Poruka: 
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
        if (preg_match('/\b(\d+)\s*(guests?|people|persons|gosta|gostiju|osoba|ljudi)\b/i', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }


}
