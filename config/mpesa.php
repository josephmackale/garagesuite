<?php

return [
    'env' => env('MPESA_ENV', 'sandbox'),

    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),

    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),

    'callback_url' => env('MPESA_CALLBACK_URL'),

    'base_urls' => [
        'sandbox' => 'https://sandbox.safaricom.co.ke',
        'production' => 'https://api.safaricom.co.ke',
    ],

    'oauth_path' => '/oauth/v1/generate',                 // ?grant_type=client_credentials :contentReference[oaicite:4]{index=4}
    'stk_push_path' => '/mpesa/stkpush/v1/processrequest',
];
