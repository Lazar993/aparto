<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Services\OpenAiService;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function search(Request $request, OpenAiService $ai)
    {
        $message = $request->input('message');

        $intent = $ai->parseReservationIntent($message);

        // Log the raw intent for debugging
        logger()->info('AI Chat intent', [
            'message' => $message,
            'intent' => $intent,
        ]);

        if (!$intent || empty($intent['city'])) {
            return response()->json([
                'reply' => __('Izvini, nisam razumeo tvoju poruku. Molim te, pokušaj ponovo sa više detalja o tome šta tražiš.'),
                'apartments' => [],
            ]);
        }

        $query = Apartment::where('active', true)
            ->where('city', 'like', '%' . $intent['city'] . '%');

        if (!empty($intent['max_price'])) {
            $query->where('price_per_night', '<=', $intent['max_price']);
        }

        $apartments = $query->take(5)->get();

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
                'reply' => __('Pronašao sam :count stan za tebe.', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }
    }
}
