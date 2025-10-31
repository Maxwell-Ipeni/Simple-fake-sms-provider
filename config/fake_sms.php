<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fake SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the fake SMS provider. The callback_url is the URL
    | that will receive simulated incoming messages. It can be overridden
    | by setting FAKE_SMS_CALLBACK_URL in your environment.
    |
    */
    'callback_url' => env('FAKE_SMS_CALLBACK_URL', 'http://127.0.0.1:8000/receive-sms'),
    // maximum number of messages to keep in cache
    'max_messages' => env('FAKE_SMS_MAX_MESSAGES', 500),
    // how many messages to return in watch/sse responses (0 = no limit)
    'watch_limit' => env('FAKE_SMS_WATCH_LIMIT', 50),
];
