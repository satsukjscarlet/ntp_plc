<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Collector tokens
    |--------------------------------------------------------------------------
    |
    | Token gốc được đặt trong .env. Database chỉ lưu SHA-256 của token.
    |
    */

    'collector_tokens' => [

        'M62' => env(
            'PLC_COLLECTOR_TOKEN_M62',
            'NTP_PLC_M62_COLLECTOR_2026_DEV'
        ),

    ],

];