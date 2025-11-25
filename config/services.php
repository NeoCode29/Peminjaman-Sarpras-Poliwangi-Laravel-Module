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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'oauth_server' => [
        'sso_enable' => env('SSO_ENABLE', false),
        'provider' => env('OAUTH_PROVIDER', 'poliwangi'),
        'client_id' => env('OAUTH_SERVER_ID', null),
        'client_secret' => env('OAUTH_SERVER_SECRET', null),
        'redirect' => env('OAUTH_SERVER_REDIRECT_URI', null),
        'uri' => env('OAUTH_SERVER_URI', null),
        'uri_logout' => env('OAUTH_SERVER_LOGOUT_URI', null),
    ],

];
