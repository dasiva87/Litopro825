<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayU Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for PayU payment gateway
    | for Colombian market integration.
    |
    */

    'api_key' => env('PAYU_API_KEY'),
    'merchant_id' => env('PAYU_MERCHANT_ID'),
    'account_id' => env('PAYU_ACCOUNT_ID'),
    'api_login' => env('PAYU_API_LOGIN'),
    'public_key' => env('PAYU_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | PayU URLs
    |--------------------------------------------------------------------------
    */

    'base_url' => env('PAYU_BASE_URL', 'https://sandbox.api.payulatam.com'),
    'reports_url' => env('PAYU_REPORTS_URL', 'https://sandbox.api.payulatam.com'),
    'payments_url' => env('PAYU_PAYMENTS_URL', 'https://sandbox.checkout.payulatam.com'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'environment' => env('PAYU_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | PayU Colombia uses COP (Colombian Pesos) as default currency
    |
    */

    'currency' => 'COP',

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Available payment methods in PayU Colombia
    |
    */

    'payment_methods' => [
        'VISA' => [
            'name' => 'Visa',
            'type' => 'credit_card',
            'enabled' => true
        ],
        'MASTERCARD' => [
            'name' => 'Mastercard',
            'type' => 'credit_card',
            'enabled' => true
        ],
        'AMEX' => [
            'name' => 'American Express',
            'type' => 'credit_card',
            'enabled' => true
        ],
        'DINERS' => [
            'name' => 'Diners Club',
            'type' => 'credit_card',
            'enabled' => true
        ],
        'PSE' => [
            'name' => 'PSE',
            'type' => 'bank_transfer',
            'enabled' => true
        ],
        'EFECTY' => [
            'name' => 'Efecty',
            'type' => 'cash',
            'enabled' => true
        ],
        'BALOTO' => [
            'name' => 'Baloto',
            'type' => 'cash',
            'enabled' => true
        ]
    ],

];