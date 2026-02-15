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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'kashtre' => [
        // Default to demo URL (https://demo.kashtre.com) unless KASHTRE_API_URL is set
        // For local development, set KASHTRE_API_URL=http://127.0.0.1:8002 in .env
        'api_url' => env('KASHTRE_API_URL', 'https://demo.kashtre.com'),
    ],

    'marzsms' => [
        'base_url' => env('MARZSMS_BASE_URL', 'https://sms.wearemarz.com/api/v1'),
        'api_key' => env('MARZSMS_API_KEY'),
        'api_secret' => env('MARZSMS_API_SECRET'),
        'verify_ssl' => env('MARZSMS_VERIFY_SSL', true),
    ],

];
