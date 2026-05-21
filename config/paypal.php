<?php

return [

    'client_id' => env('PAYPAL_CLIENT_ID'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET'),

    /** sandbox or live */
    'mode' => env('PAYPAL_MODE', 'sandbox'),

    /** Webhook ID from PayPal Developer Dashboard (for signature verification) */
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

    'currency' => env('PAYPAL_CURRENCY', 'EUR'),

];
