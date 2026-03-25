<?php

namespace App\Http\Controllers;

use App\Services\AiSearchService;
use App\Services\OpenAiService;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function __construct(
        private AiSearchService $aiSearchService,
    ) {}

    public function search(Request $request, OpenAiService $ai)
    {
        $message = $request->input('message');

        $intent = $ai->parseReservationIntent($message);
        $intent = $this->aiSearchService->normalizeIntentDates($intent, $message);

        logger()->info('AI Chat intent', [
            'message' => $message,
            'intent' => $intent,
        ]);

        if (! $intent) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.not_understood'),
                'apartments' => [],
            ]);
        } elseif ($intent && empty($intent['city'])) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.missing_city'),
                'apartments' => [],
            ]);
        }

        $apartments = $this->aiSearchService->filterApartments($intent);
        $count = $apartments->count();

        if ($count === 0) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.no_results'),
                'apartments' => [],
            ]);
        }

        if ($count === 1) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.found_one', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }

        if ($count < 5 && $count > 1) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.found_few', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }

        if ($count >= 5) {
            return response()->json([
                'reply' => __('frontpage.ai.responses.found_many', ['count' => $count]),
                'apartments' => $apartments,
            ]);
        }
    }
}
