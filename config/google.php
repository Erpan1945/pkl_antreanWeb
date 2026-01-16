<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Sheets Service
    |--------------------------------------------------------------------------
    */
    'service' => [
        /*
         * Enable service account.
         */
        'enable' => true,

        /*
         * Path to service account json file.
         */
        'file' => env('GOOGLE_SERVICE_ACCOUNT_JSON_LOCATION', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        \Google\Service\Sheets::SPREADSHEETS, // Akses baca/tulis Spreadsheet
    ],

    'access_type' => 'offline',
    'approval_prompt' => 'auto',
    'prompt' => 'consent',

    /*
    |--------------------------------------------------------------------------
    | Spreadsheet ID
    |--------------------------------------------------------------------------
    */
    'spreadsheet_id' => env('GOOGLE_SPREADSHEET_ID', ''),
];