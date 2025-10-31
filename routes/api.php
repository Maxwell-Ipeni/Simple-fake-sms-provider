<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Http\Controllers\SmsSimulatorController;

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

Route::post('/send-message', [SmsSimulatorController::class, 'store']);

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

    // also keep the aggregate key used by watch/SSE so counts are accurate
    $allKey = 'sms:all_messages';
    $all = FacadesCache::get($allKey, []);
    if (!is_array($all)) { $all = []; }
    $all[] = $message;
    if (count($all) > $max) { $all = array_slice($all, -1 * $max); }
    FacadesCache::put($allKey, $all, now()->addDays(1));

    return response()->json(['status' => 'ok'], 200);
});

Route::get('/cache-watch', [SmsSimulatorController::class, 'watchCache']);
Route::get('/sse', [SmsSimulatorController::class, 'sse']);