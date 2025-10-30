<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsCallbackService implements SmsCallbackServiceInterface
{
    public function sendCallback(array $payload): array
    {
        $callbackUrl = config('fake_sms.callback_url');

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($callbackUrl, $payload);

            if ($response->successful()) {
                return ['delivered' => true];
            }

            return ['delivered' => false, 'error' => 'Callback returned status ' . $response->status()];
        } catch (\Exception $e) {
            return ['delivered' => false, 'error' => $e->getMessage()];
        }
    }
}
