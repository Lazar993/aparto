<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HCaptchaService
{
    public function isConfigured(): bool
    {
        return (string) config('services.hcaptcha.site_key') !== ''
            && (string) config('services.hcaptcha.secret') !== '';
    }

    /**
     * @return array{0: bool, 1: array}
     */
    public function verify(string $token, string $ip): array
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://api.hcaptcha.com/siteverify', [
                    'secret' => (string) config('services.hcaptcha.secret'),
                    'response' => $token,
                    'remoteip' => $ip,
                    'sitekey' => (string) config('services.hcaptcha.site_key'),
                ]);

            if (! $response->ok()) {
                return [false, ['request-failed']];
            }

            $json = $response->json() ?? [];

            if (! empty($json['success'])) {
                return [true, []];
            }

            return [false, $json['error-codes'] ?? []];
        } catch (\Throwable $exception) {
            Log::warning('hCaptcha verification request failed.', [
                'ip' => $ip,
                'error' => $exception->getMessage(),
            ]);

            return [false, ['request-failed']];
        }
    }
}
