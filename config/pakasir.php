<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PaKasir Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'base_url' => env('PAKASIR_BASE_URL', 'https://app.pakasir.com'),
    'project_slug' => env('PAKASIR_PROJECT_SLUG', ''),
    'api_key' => env('PAKASIR_API_KEY', ''),
    'sandbox' => env('PAKASIR_SANDBOX', true),
];
