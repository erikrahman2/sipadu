<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workflow States
    |--------------------------------------------------------------------------
    */

    'states' => [
        'DRAFT'                    => 'Draft',
        'SUBMITTED'                => 'Unreviewed',
        'OCR_PROCESSED'            => 'OCR Diproses',
        'PA_REVIEW'                => 'Review PA',
        'DISDUKCAPIL_VALIDATION'   => 'Validasi Disdukcapil',
        'COMPLETED'                => 'Selesai',
        'ARCHIVED'                 => 'Diarsipkan',
        'REJECTED'                 => 'Ditolak',
    ],

    /*
    |--------------------------------------------------------------------------
    | Valid Transitions
    |--------------------------------------------------------------------------
    */

    'transitions' => [
        'DRAFT'                  => ['SUBMITTED'],
        'SUBMITTED'              => ['OCR_PROCESSED', 'REJECTED'],
        'OCR_PROCESSED'          => ['PA_REVIEW', 'REJECTED'],
        'PA_REVIEW'              => ['DISDUKCAPIL_VALIDATION', 'REJECTED'],
        'DISDUKCAPIL_VALIDATION' => ['COMPLETED', 'REJECTED'],
        'COMPLETED'              => ['ARCHIVED'],
        'REJECTED'               => ['DRAFT'],
        'ARCHIVED'               => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role required for each state transition
    |--------------------------------------------------------------------------
    */

    'transition_roles' => [
        // PA Assistant: ajukan permohonan & revisi setelah ditolak
        'DRAFT->SUBMITTED'                           => ['pa_assistant'],
        // PA Assistant: teruskan ke PA Review setelah OCR selesai
        'SUBMITTED->OCR_PROCESSED'                   => ['system', 'pa_assistant'],
        'OCR_PROCESSED->PA_REVIEW'                   => ['pa_assistant', 'pa_management'],
        // PA Management: approve (ke Disdukcapil) atau reject pengajuan
        'PA_REVIEW->DISDUKCAPIL_VALIDATION'          => ['pa_management'],
        'PA_REVIEW->REJECTED'                        => ['pa_management'],
        // Disdukcapil Staff: validasi akhir & selesaikan / tolak
        'DISDUKCAPIL_VALIDATION->COMPLETED'          => ['disdukcapil_staff'],
        'DISDUKCAPIL_VALIDATION->REJECTED'           => ['disdukcapil_staff'],
        // PA Staff: arsip & serah-terima dokumen selesai
        'COMPLETED->ARCHIVED'                        => ['pa_staff', 'system'],
        // PA Assistant: revisi & ajukan ulang
        'REJECTED->DRAFT'                            => ['pa_assistant'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification channels per state
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'SUBMITTED'              => ['submitter', 'pa_assistant'],
        'OCR_PROCESSED'          => ['pa_assistant', 'pa_management'],
        'PA_REVIEW'              => ['pa_management'],
        'DISDUKCAPIL_VALIDATION' => ['disdukcapil_staff'],
        'COMPLETED'              => ['submitter', 'pa_staff'],
        'REJECTED'               => ['submitter'],
        'ARCHIVED'               => ['pa_staff'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking token config
    |--------------------------------------------------------------------------
    */

    'tracking_token_length' => 32,
    'tracking_token_prefix' => 'TRK',
];
