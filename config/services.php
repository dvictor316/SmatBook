<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URL'), // Changed from URI to URL to match your .env
    'maps_key'      => env('GOOGLE_MAPS_KEY'),
],

'facebook' => [
    'client_id'     => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect'      => env('FACEBOOK_REDIRECT_URL'), // Changed from URI to URL to match your .env
],

'stripe' => [
    'model'  => App\Models\User::class,
    'key'    => env('STRIPE_TEST_PUBLISHABLE_KEY', env('STRIPE_KEY', env('STRIPE_PUBLISHABLE_KEY'))),
    'secret' => env('STRIPE_TEST_SECRET_KEY', env('STRIPE_SECRET', env('STRIPE_SECRET_KEY'))),
],

'paystack' => [
    'publicKey' => env('PAYSTACK_TEST_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY')),
    'secretKey' => env('PAYSTACK_TEST_SECRET_KEY', env('PAYSTACK_SECRET_KEY')),
    'public_key' => env('PAYSTACK_TEST_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY')),
    'secret_key' => env('PAYSTACK_TEST_SECRET_KEY', env('PAYSTACK_SECRET_KEY')),
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL'),
],

'flutterwave' => [
    'public_key' => env('FLUTTERWAVE_TEST_PUBLIC_KEY', env('FLUTTERWAVE_PUBLIC_KEY')),
    'secret_key' => env('FLUTTERWAVE_TEST_SECRET_KEY', env('FLUTTERWAVE_SECRET_KEY')),
],

'opay' => [
    'public_key' => env('OPAY_PUBLIC_KEY'),
    'secret_key' => env('OPAY_SECRET_KEY'),
    'merchant_id' => env('OPAY_MERCHANT_ID'),
],

'moniepoint' => [
    'key' => env('MONIEPOINT_API_KEY'),
],

'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
],

'dojah' => [
    'app_id'     => env('DOJAH_APP_ID'),
    'secret_key' => env('DOJAH_SECRET_KEY'),
    'base_url'   => env('DOJAH_BASE_URL', 'https://api.dojah.io'),
],
];
