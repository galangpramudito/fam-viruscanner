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

    'virustotal' => [
        'api_key' => env('VIRUSTOTAL_API_KEY'),
        'base_url' => env('VIRUSTOTAL_BASE_URL', 'https://www.virustotal.com/api/v3'),
        'poll_interval_seconds' => env('VIRUSTOTAL_POLL_INTERVAL', 4),
        'max_poll_attempts' => env('VIRUSTOTAL_MAX_POLL_ATTEMPTS', 15),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'nex-agi/nex-n2-pro:free'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'referer' => env('APP_URL'),
    ],

    'tokenrouter' => [
        'api_key' => env('TOKENROUTER_API_KEY'),
        'model' => env('TOKENROUTER_MODEL', 'MiniMax-M3'),
        'base_url' => env('TOKENROUTER_BASE_URL', 'https://api.tokenrouter.com/v1'),
        'referer' => env('APP_URL'),
    ],

];
