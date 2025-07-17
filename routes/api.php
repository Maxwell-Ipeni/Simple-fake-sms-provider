<?php

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\Route;

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
    $data = FacadesCache::get("data");
    
    $message = array();
    $message['sender'] = 0;
    $message['content'] = $request->text;

    $previousMessage = $data[count($data) - 1];
    $message['number'] = $previousMessage['number'];

    $client = new Client();
    $client->request('POST', "http://127.0.0.1:8000/receive-sms", $message);

    FacadesCache::put('data', $data, now()->addDays(1));

    return response("success", 200);
});

Route::post('/get-message', function(Request $request){
    $data = FacadesCache::get("data");

    $message = array();
    $message['sender'] = 1;
    $message['content'] = $request->text;
    $message['number'] = $request->number;

    array_push($data, $message);

    FacadesCache::put('data', $data, now()->addDays(1));

    return response("success", 200);
});

Route::get('/cache-watch', function(Request $request){
    $data = FacadesCache::get("data");
    FacadesCache::put('message', $data, now()->addDays(1));
    return FacadesCache::get("data");
});