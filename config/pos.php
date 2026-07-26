<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application session & security settings
    |--------------------------------------------------------------------------
    |
    | These values control inactivity lockouts and step-up authentication
    | thresholds for the Infinity POS. They are intentionally conservative;
    | tune them for the rhythm of the shop you operate.
    |
    */

    'session' => [
        'idle_minutes' => env('POS_IDLE_MINUTES', 15),
    ],
];
