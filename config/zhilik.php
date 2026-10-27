<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Жілік сындыру game settings
    |--------------------------------------------------------------------------
    */

    'max_players' => (int) env('ZHILIK_MAX_PLAYERS', 100),

    // The first player to reach exactly this many taps breaks the bone.
    'target_taps' => (int) env('ZHILIK_TARGET_TAPS', 150),

    // Time limit if nobody reaches the target; then the most taps wins.
    'max_seconds' => (int) env('ZHILIK_MAX_SECONDS', 30),

    'countdown_seconds' => (int) env('ZHILIK_COUNTDOWN_SECONDS', 3),

    // Taps above this rate are treated as cheating and clamped.
    'max_taps_per_second' => (int) env('ZHILIK_MAX_TAPS_PER_SECOND', 20),

    // How often phones send their cumulative tap count, in milliseconds.
    'tap_sync_ms' => (int) env('ZHILIK_TAP_SYNC_MS', 400),

    // Minimum interval between score broadcasts, in milliseconds.
    'score_broadcast_ms' => (int) env('ZHILIK_SCORE_BROADCAST_MS', 250),

    // Late tap updates (and other finishers) are still accepted for this long after the round stops.
    'grace_seconds' => (int) env('ZHILIK_GRACE_SECONDS', 1),

    // How many players the big screen leaderboard shows.
    'leaderboard_size' => (int) env('ZHILIK_LEADERBOARD_SIZE', 10),

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

];
