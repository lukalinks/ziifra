<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP client SSL verification
    |--------------------------------------------------------------------------
    |
    | On Windows, PHP often lacks a CA bundle and outbound HTTPS calls fail with
    | cURL error 60. Point ca_bundle at a PEM file (run: php artisan app:install-ca-bundle)
    | or set verify_ssl to false in local .env only (not recommended for production).
    |
    */

    'verify_ssl' => env('HTTP_VERIFY_SSL', true),

    'ca_bundle' => env('HTTP_CA_BUNDLE', storage_path('certs/cacert.pem')),

];
