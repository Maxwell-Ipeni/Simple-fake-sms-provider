<?php

namespace App\Services;

interface SmsCallbackServiceInterface
{
    /**
     * Send callback payload to configured endpoint.
     * Returns array with keys: delivered (bool) and optional error (string).
     *
     * @param array $payload
     * @return array
     */
    public function sendCallback(array $payload): array;
}
