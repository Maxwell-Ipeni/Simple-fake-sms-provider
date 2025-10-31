<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\SmsCallbackServiceInterface;
use Illuminate\Support\Carbon;

class SmsSimulatorController extends Controller
{
    /**
     * Store a provider-sent message.
     *
     * Accepts JSON: { number: string, text: string }
     * Validates input, stores message in cache under key "sms:sent_messages",
     * and returns 201 with the stored message id.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate - returns 422 automatically if validation fails
        $validated = $request->validate([
            'number' => 'required|string',
            'text'   => 'required|string',
        ]);

        // Build message record
        $id = (string) Str::uuid();
        $message = [
            'id'        => $id,
            'timestamp' => now()->toIso8601String(),
            'number'    => $validated['number'],
            // normalize to `content` which the UI expects
            'content'   => $validated['text'],
            'sender'    => 0, // provider
        ];

        // Cache key and storage
        $key = 'sms:sent_messages';
        $messages = Cache::get($key, []);
        if (!is_array($messages)) {
            $messages = [];
        }

        $messages[] = $message;

        // Optional: respect configured max size
        $max = (int) config('fake_sms.max_messages', 500);
        if (count($messages) > $max) {
            $messages = array_slice($messages, -1 * $max);
        }

        // Persist the messages (7 days TTL)
        Cache::put($key, $messages, now()->addDays(7));

        // Also persist to the legacy 'messages' key so existing UI/tests see the new message
        $legacyKey = 'messages';
        $legacy = Cache::get($legacyKey, []);
        if (!is_array($legacy)) {
            $legacy = [];
        }
        $legacy[] = $message;
        if (count($legacy) > $max) {
            $legacy = array_slice($legacy, -1 * $max);
        }
    Cache::put($legacyKey, $legacy, now()->addDays(7));

    // Keep a canonical aggregate key so frontend watch/SSE can read a single source
    $allKey = 'sms:all_messages';
    $all = Cache::get($allKey, []);
    if (!is_array($all)) { $all = []; }
    $all[] = $message;
    if (count($all) > $max) { $all = array_slice($all, -1 * $max); }
    Cache::put($allKey, $all, now()->addDays(7));

        // Send callback to configured URL using the injected SmsCallbackService
        $callbackService = app(SmsCallbackServiceInterface::class);

        $result = $callbackService->sendCallback([
            'number' => $message['number'],
            // callback expects `text` key - provide the content value
            'text' => $message['content'],
            'provider' => 'fake-sms',
            'timestamp' => $message['timestamp'],
        ]);

        $resp = ['status' => 'ok', 'delivered' => (bool) ($result['delivered'] ?? false)];
        if (!empty($result['error'])) {
            $resp['error'] = $result['error'];
        }

        // Return 200 for compatibility with existing API behavior/tests
        return response()->json($resp, 200);
    }

    /**
     * Return cached messages for debugging.
     *
     * This reads from the `sms:all_messages` cache key and returns a structured
     * JSON payload containing the key name, count and the messages array.
     */
    public function watchCache(Request $request): JsonResponse
    {
        $key = 'sms:all_messages';

        $messages = Cache::get($key);
        if (!is_array($messages)) {
            // fall back to legacy keys if needed
            $messages = Cache::get('messages', []);
            if (!is_array($messages)) {
                $messages = Cache::get('sms:sent_messages', []);
            }
        }

        // respect a watch limit to avoid returning huge payloads
        $limit = (int) config('fake_sms.watch_limit', 50);
        $count = is_array($messages) ? count($messages) : 0;
        if ($limit > 0 && is_array($messages) && $count > $limit) {
            $messages = array_slice($messages, -1 * $limit);
        }

        $payload = [
            'key' => $key,
            'count' => $count,
            'messages' => $messages,
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($payload, 200);
    }

    /**
     * Server-Sent Events endpoint that streams cache updates.
     *
     * This method emits a JSON payload whenever the cached messages change.
     */
    public function sse(Request $request)
    {
        $key = 'sms:all_messages';

        // allow long-running streaming in dev (avoid PHP max execution timeout)
        @set_time_limit(0);
        @ignore_user_abort(true);

        return response()->stream(function() use ($key) {
            $lastPayload = null;
            $start = time();
            // limit streaming duration per connection to avoid runaway processes in dev
            $maxSeconds = (int) config('fake_sms.sse_max_seconds', 300);
            while (true) {
                $messages = Cache::get($key);
                if (!is_array($messages)) {
                    $messages = Cache::get('messages', []);
                    if (!is_array($messages)) {
                        $messages = Cache::get('sms:sent_messages', []);
                    }
                }

                // limit how many messages we include in the SSE payload
                $limit = (int) config('fake_sms.watch_limit', 50);
                $count = is_array($messages) ? count($messages) : 0;
                if ($limit > 0 && is_array($messages) && $count > $limit) {
                    $messages = array_slice($messages, -1 * $limit);
                }

                $payload = [
                    'key' => $key,
                    'count' => $count,
                    'messages' => $messages,
                    'timestamp' => now()->toIso8601String(),
                ];

                $json = json_encode($payload);
                if ($json !== $lastPayload) {
                    echo "data: {$json}\n\n";
                    // flush to the client
                    if (function_exists('ob_flush')) { @ob_flush(); }
                    flush();
                    $lastPayload = $json;
                }

                // break if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // auto-close after configured max seconds to keep processes bounded
                if ($maxSeconds > 0 && (time() - $start) > $maxSeconds) {
                    break;
                }

                // small sleep to avoid busy loop — increased to reduce CPU cost per connection
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
