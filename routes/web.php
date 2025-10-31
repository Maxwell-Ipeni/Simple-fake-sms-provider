<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('fake-sms');
});

// Endpoint for external callback receivers (default FAKE_SMS_CALLBACK_URL points here)
Route::post('/receive-sms', function (\Illuminate\Http\Request $request) {
    $payload = $request->only(['number', 'text', 'provider']);

    $validator = validator($payload, [
        'number' => ['required', 'string'],
        'text' => ['required', 'string'],
        'provider' => ['nullable', 'string'],
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
    }

    $messages = \Illuminate\Support\Facades\Cache::get('messages');
    if (!is_array($messages)) {
        $messages = [];
    }

    $message = [
        'number' => (string) $payload['number'],
        'content' => (string) $payload['text'],
        'sender' => 1, // incoming/user
        'provider' => $payload['provider'] ?? 'external',
        'timestamp' => now()->toIso8601String(),
    ];

    $messages[] = $message;
    $max = (int) config('fake_sms.max_messages', 500);
    if (count($messages) > $max) {
        $messages = array_slice($messages, -1 * $max);
    }

    \Illuminate\Support\Facades\Cache::put('messages', $messages, now()->addDays(1));

    return response()->json(['status' => 'ok'], 200);
});