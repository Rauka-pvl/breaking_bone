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

    'sides' => ['left', 'right'],

];
