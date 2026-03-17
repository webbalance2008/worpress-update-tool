<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    'health_check' => [
        'timeout' => env('WUM_HEALTH_CHECK_TIMEOUT', 15),
        'delay_after_update' => env('WUM_HEALTH_CHECK_DELAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Configuration
    |--------------------------------------------------------------------------
    */
    'heartbeat' => [
        'interval_seconds' => env('WUM_HEARTBEAT_INTERVAL', 300),
        'stale_threshold_minutes' => env('WUM_STALE_THRESHOLD', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Configuration
    |--------------------------------------------------------------------------
    */
    'updates' => [
        'max_concurrent_per_site' => env('WUM_MAX_CONCURRENT_UPDATES', 1),
        'job_timeout' => env('WUM_UPDATE_JOB_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | HMAC Configuration
    |--------------------------------------------------------------------------
    */
    'hmac' => [
        'max_age_seconds' => env('WUM_HMAC_MAX_AGE', 300),
    ],
];
