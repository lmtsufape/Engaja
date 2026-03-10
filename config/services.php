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

    'limesurvey' => [
        'url' => env('LIMESURVEY_URL'),
        'username' => env('LIMESURVEY_USERNAME'),
        'password' => env('LIMESURVEY_PASSWORD'),
        'survey_id' => env('LIMESURVEY_SURVEY_ID'),
        'verify_ssl' => env('LIMESURVEY_VERIFY_SSL', true),
        'cafile' => env('LIMESURVEY_CAFILE'),
        'municipio_field' => env('LIMESURVEY_MUNICIPIO_FIELD'),
        'matrix_column_labels' => env('LIMESURVEY_MATRIX_COLUMN_LABELS'),
        'timeout' => env('LIMESURVEY_TIMEOUT', 30),
        'cache_minutes' => env('LIMESURVEY_CACHE_MINUTES', 5),
    ],

];
