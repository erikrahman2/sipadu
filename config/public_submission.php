<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Pengajuan per NIK
    |--------------------------------------------------------------------------
    |
    | Satu NIK hanya dapat mengajukan sebanyak `max_per_nik` kali
    | dalam rentang `limit_days` hari terakhir.
    | Nilai ini hanya untuk referensi konfigurasi; logika utama ada di Model.
    |
    */

    'max_per_nik'       => (int) env('PUBLIC_SUBMISSION_MAX', 3),
    'limit_days'        => (int) env('PUBLIC_SUBMISSION_LIMIT_DAYS', 15),

    /*
    |--------------------------------------------------------------------------
    | Upload Dokumen
    |--------------------------------------------------------------------------
    */

    'max_file_size_mb'      => (int) env('PUBLIC_SUBMISSION_MAX_FILE_MB', 10),
    'max_files_per_type'    => 1,
    'allowed_mimes'         => ['jpg', 'jpeg', 'png', 'pdf'],

    /*
    |--------------------------------------------------------------------------
    | Notifikasi WhatsApp
    |--------------------------------------------------------------------------
    |
    | Apakah notifikasi WA diaktifkan.
    | Set false untuk menonaktifkan pengiriman WA (berguna saat testing).
    |
    */

    'wa_notification_enabled' => (bool) env('PUBLIC_SUBMISSION_WA', true),

];
