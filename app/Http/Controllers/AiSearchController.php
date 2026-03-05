<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Services\OpenAiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function search(Request $request, OpenAiService $ai)
    {
        $message = $request->input('message');

        $intent = $ai->parseReservationIntent($message);
        $intent = $this->normalizeIntentDates($intent, $message);

        // Log the raw intent for debugging
        logger()->info('AI Chat intent', [
            'message' => $message,
            'intent' => $intent,
        ]);

        if (!$intent) {
            return response()->json([
                'reply' => __('Izvini, nisam razumeo tvoju poruku. Molim te, pokušaj ponovo sa više detalja o tome šta tražiš.'),
                'apartments' => [],
            ]);
        }elseif($intent && empty($intent['city'])) {
            return response()->json([
                'reply' => __('Molim te, pokušaj ponovo i uključi naziv grada.'),
                'apartments' => [],
            ]);
        }

        $query = Apartment::where('active', true)
            ->where('city', 'like', '%' . $intent['city'] . '%');

        if (!empty($intent['max_price'])) {
            $query->where('price_per_night', '<=', $intent['max_price']);
        }

        if (!empty($intent['guests'])) {
            $query->where('guest_number', '>=', $intent['guests']);
        }

        $from = null;
        $to = null;

        if (!empty($intent['date_from']) && !empty($intent['date_to'])) {
            $from = $intent['date_from'];
            $to = $intent['date_to'];

            $query->whereDoesntHave('reservations', function ($q) use ($from, $to) {
                $q->where('status', '!=', 'canceled')
                    ->where('date_from', '<', $to)
                    ->where('date_to', '>', $from);
            });

            // Blocked periods are inclusive date ranges in admin UI.
            $query->whereDoesntHave('blockedPeriods', function ($q) use ($from, $to) {
                $q->where('date_from', '<', $to)
                    ->where('date_to', '>=', $from);
            });
        }

        if (!empty($intent['rooms'])) {
            $query->where('rooms', '>=', $intent['rooms']);
        }

        $apartments = $query->get();

        $apartments = $apartments->take(5)->values();

        $count = $apartments->count();

        if($count === 0) {
            return response()->json([
                'reply' => __('Nažalost, nisam pronašao nijedan stan koji odgovara tvojim kriterijumima. Možda možeš pokušati sa drugačijim parametrima ili još uvek nema dostupnih stanova.'),
                'apartments' => [],
            ]);
        }

        if($count === 1) {
            return response()->json([
                'reply' => __('Pronašao sam :count stan za tebe.', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }

        if($count < 5 && $count > 1) {
            return response()->json([
                'reply' => __('Pronašao sam :count stana koji odgovaraju tvojim kriterijumima.', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }

        if($count >= 5) {
            return response()->json([
                'reply' => __('Pronašao sam :count stanova za tebe.', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }
    }

    private function normalizeIntentDates(array $intent, string $message): array
    {
        if (empty($intent['date_from']) || empty($intent['date_to'])) {
            return $intent;
        }

        $hasExplicitYear = preg_match('/\b\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}\b|\b\d{4}[.\/-]\d{1,2}[.\/-]\d{1,2}\b/', $message) === 1;

        // If year was explicitly provided by user, trust AI output.
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

        // Handle ranges that cross New Year (e.g. 30.12 - 03.01).
        if ($to->lt($from)) {
            $to->addYear();
        }

        // Search should target upcoming dates when user doesn't specify year.
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
}
