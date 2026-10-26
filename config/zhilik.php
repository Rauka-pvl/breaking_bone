<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Жілік сындыру game settings
    |--------------------------------------------------------------------------
    */

    'max_per_side' => (int) env('ZHILIK_MAX_PER_SIDE', 50),

    'round_seconds' => (int) env('ZHILIK_ROUND_SECONDS', 10),

    'countdown_seconds' => (int) env('ZHILIK_COUNTDOWN_SECONDS', 3),

    // Taps above this rate are treated as cheating and clamped.
    'max_taps_per_second' => (int) env('ZHILIK_MAX_TAPS_PER_SECOND', 20),

    // How often phones send their cumulative tap count, in milliseconds.
    'tap_sync_ms' => (int) env('ZHILIK_TAP_SYNC_MS', 400),

    // Minimum interval between score broadcasts, in milliseconds.
    'score_broadcast_ms' => (int) env('ZHILIK_SCORE_BROADCAST_MS', 250),

    // Late tap updates are still accepted for this long after the round ends.
    'grace_seconds' => (int) env('ZHILIK_GRACE_SECONDS', 1),

    // Password for /admin, where host keys are managed. Admin is disabled when empty.
    'admin_password' => env('ZHILIK_ADMIN_PASSWORD'),

    // Each host key gets its own channel: "{channel}.{room code}".
    'channel' => env('ZHILIK_CHANNEL', 'zhilik.room'),

    // Where browsers connect to Reverb. Can differ from REVERB_HOST, which the
    // server uses to publish (e.g. 127.0.0.1:8080 behind an nginx wss proxy).
    'client_reverb' => [
        'key' => env('VITE_REVERB_APP_KEY') ?: env('REVERB_APP_KEY'),
        'host' => env('VITE_REVERB_HOST') ?: env('REVERB_HOST'),
        'port' => (int) (env('VITE_REVERB_PORT') ?: env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME') ?: env('REVERB_SCHEME', 'https'),
    ],

    'sides' => ['left', 'right'],

];
