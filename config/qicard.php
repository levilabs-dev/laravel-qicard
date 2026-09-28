<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default QiCard Terminal
    |--------------------------------------------------------------------------
    |
    | The terminal used when no explicit terminal is requested. Matches a key
    | in the "terminals" array below.
    |
    */
    'default' => env('QICARD_TERMINAL', 'default'),

    /*
    |--------------------------------------------------------------------------
    | QiCard Terminals
    |--------------------------------------------------------------------------
    |
    | Each terminal holds its own credentials so a single application can
    | accept payments through multiple QiCard Merchant Terminals. Every
    | request is authenticated with Basic Auth (username/password) and scoped
    | to a terminal via the X-Terminal-Id header.
    |
    | environment: "sandbox" or "production" — purely a label used for docs
    | and the sync command; it does not select the base_url below (QiCard's
    | production host is not publicly documented and is issued per merchant
    | by your acquirer — set base_url explicitly once you have it).
    |
    | public_key: the Payment Gateway's RSA public key (PEM format, literal
    | \n for newlines in .env), used only to verify the X-Signature header on
    | incoming webhook notifications. Provided by QiCard alongside your
    | terminal credentials.
    |
    */
    'terminals' => [
        'default' => [
            'environment' => env('QICARD_ENVIRONMENT', 'sandbox'),
            'terminal_id' => env('QICARD_TERMINAL_ID'),
            'username' => env('QICARD_USERNAME'),
            'password' => env('QICARD_PASSWORD'),
            'base_url' => env('QICARD_BASE_URL', 'https://uat-sandbox-3ds-api.qi.iq/api/v1'),
            'public_key' => env('QICARD_PUBLIC_KEY') ? str_replace('\n', "\n", (string) env('QICARD_PUBLIC_KEY')) : null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency & Locale
    |--------------------------------------------------------------------------
    |
    | currency: ISO 4217 code. QiCard's own examples use IQD throughout.
    | locale: the language of the hosted payment form. Falls back to the
    | Merchant Terminal's configured default locale when omitted.
    |
    */
    'currency' => env('QICARD_CURRENCY', 'IQD'),
    'locale' => env('QICARD_LOCALE'),

    /*
    |--------------------------------------------------------------------------
    | Redirect & Notification URLs
    |--------------------------------------------------------------------------
    |
    | finish_url: where the payer's browser is redirected once payment
    | processing completes (success, failure, or authentication failure alike
    | — always re-verify with QicardPayment::status() before trusting it).
    | notification_url: where QiCard POSTs the webhook notification once the
    | payment reaches a terminal status. QiCard retries until it gets an
    | HTTP 200 back, so make sure this route always returns one once you've
    | recorded the notification.
    |
    */
    'finish_url' => env('QICARD_FINISH_URL'),
    'notification_url' => env('QICARD_NOTIFICATION_URL'),

    /*
    |--------------------------------------------------------------------------
    | Mobile Payments
    |--------------------------------------------------------------------------
    |
    | Payments initiated from a mobile app must be created with appChannel =
    | true so the gateway tailors the authentication flow for an in-app
    | context — forgetting this returns a "payment is not in app channel"
    | error from QiCard the moment the mobile SDK tries to use it. This is a
    | default only; pass $appChannel explicitly to create() to override it
    | per call.
    |
    */
    'app_channel' => env('QICARD_APP_CHANNEL', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | TLS certificate verification is always enabled and cannot be disabled
    | through configuration.
    |
    */
    'http' => [
        'timeout' => (int) env('QICARD_HTTP_TIMEOUT', 15),
        'retry_times' => (int) env('QICARD_HTTP_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('QICARD_HTTP_RETRY_SLEEP_MS', 200),
    ],

];
