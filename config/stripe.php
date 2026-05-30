<?php

return [

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'eur'),

    /**
     * Checkout Session payment methods (comma-separated in STRIPE_PAYMENT_METHOD_TYPES).
     * Required when Stripe Dashboard has no default methods for your currency.
     */
    'payment_method_types' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('STRIPE_PAYMENT_METHOD_TYPES', 'card'))
    ))),

];
