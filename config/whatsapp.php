<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway WhatsApp
    |--------------------------------------------------------------------------
    |
    | Driver yang tersedia: fonnte | wablas | woowa | log
    |
    | Gunakan driver 'log' untuk development/testing — pesan hanya ditulis
    | ke laravel.log, tidak benar-benar terkirim.
    |
    */

    'driver' => env('WA_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Fonnte (https://fonnte.com)
    |--------------------------------------------------------------------------
    */
    'fonnte' => [
        'token'   => env('WA_FONNTE_TOKEN', ''),
        'api_url' => 'https://api.fonnte.com/send',
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Wablas (https://wablas.com)
    |--------------------------------------------------------------------------
    */
    'wablas' => [
        'token'   => env('WA_WABLAS_TOKEN', ''),
        'domain'  => env('WA_WABLAS_DOMAIN', 'solo.wablas.com'),
        'api_url' => 'https://{domain}/api/send-message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengirim / nama aplikasi (muncul di WA jika didukung gateway)
    |--------------------------------------------------------------------------
    */
    'sender_name' => env('WA_SENDER_NAME', 'SiPadu – PA & Disdukcapil'),

    /*
    |--------------------------------------------------------------------------
    | Timeout request HTTP (detik)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('WA_TIMEOUT', 15),
];
