<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsCallbackService implements SmsCallbackServiceInterface
{
    public function sendCallback(array $payload): array
    {
        $callbackUrl = config('fake_sms.callback_url');

        // If no callback URL is configured, return immediately to avoid blocking.
        if (empty($callbackUrl)) {
            return ['delivered' => false, 'error' => 'No callback_url configured'];
        }

        try {
            // Use a short timeout so an unreachable callback doesn't hang the request.
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(2)
                ->post($callbackUrl, $payload);

            if ($response->successful()) {
                return ['delivered' => true];
            }

            return ['delivered' => false, 'error' => 'Callback returned status ' . $response->status()];
        } catch (\Exception $e) {
            // Log the exception and return quickly; do not let callback failures block main flow.
            report($e);
            return ['delivered' => false, 'error' => $e->getMessage()];
        }
    }
}
