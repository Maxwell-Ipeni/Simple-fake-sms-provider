<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/send-message', function(Request $request){
    // Validate input: text required, number optional (inferred from last message)
    $payload = $request->only(['text', 'number']);

    $validator = validator($payload, [
        'text' => ['required', 'string'],
        'number' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
    }

    $messages = FacadesCache::get('messages');
    if (!is_array($messages)) {
        $messages = [];
    }

    // If number not provided, infer from last message
    $number = $payload['number'] ?? null;
    if (empty($number)) {
        $last = end($messages);
        if ($last && isset($last['number'])) {
            $number = $last['number'];
        }
    }

    if (empty($number)) {
        return response()->json(['error' => 'number is required either in payload or as last known message'], 422);
    }

    $message = [
        'number' => (string) $number,
        'content' => (string) $payload['text'],
        'sender' => 0, // provider
        'timestamp' => now()->toIso8601String(),
    ];

    // append and trim to max messages
    $messages[] = $message;
    $max = (int) config('fake_sms.max_messages', 500);
    if (count($messages) > $max) {
        $messages = array_slice($messages, -1 * $max);
    }

    FacadesCache::put('messages', $messages, now()->addDays(1));

    // Send callback to configured URL using the injected SmsCallbackService
    $callbackService = app(\App\Services\SmsCallbackServiceInterface::class);

    $result = $callbackService->sendCallback([
        'number' => $message['number'],
        'text' => $message['content'],
        'provider' => 'fake-sms',
        'timestamp' => $message['timestamp'],
    ]);

    $resp = ['status' => 'ok', 'delivered' => (bool) ($result['delivered'] ?? false)];
    if (!empty($result['error'])) {
        $resp['error'] = $result['error'];
    }

    return response()->json($resp, 200);
});

Route::post('/get-message', function(Request $request){
    $payload = $request->only(['number', 'text']);

    $validator = validator($payload, [
        'number' => ['required', 'string'],
        'text' => ['required', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
    }

    $messages = FacadesCache::get('messages');
    if (!is_array($messages)) {
        $messages = [];
    }

    $message = [
        'number' => (string) $payload['number'],
        'content' => (string) $payload['text'],
        'sender' => 1, // user
        'timestamp' => now()->toIso8601String(),
    ];

    $messages[] = $message;
    $max = (int) config('fake_sms.max_messages', 500);
    if (count($messages) > $max) {
        $messages = array_slice($messages, -1 * $max);
    }

    FacadesCache::put('messages', $messages, now()->addDays(1));

    return response()->json(['status' => 'ok'], 200);
});

Route::get('/cache-watch', function(Request $request){
    $messages = FacadesCache::get('messages');
    if (!is_array($messages)) {
        $messages = [];
        FacadesCache::put('messages', $messages, now()->addDays(1));
    }

    return response()->json(['messages' => $messages], 200);
});