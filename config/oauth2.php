<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth2 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the OAuth2 Flow Visualizer with Passolution defaults
    |
    */

    'default_providers' => [
        'passolution' => [
            'authorization_endpoint' => env('PASSOLUTION_AUTH_URL', 'https://web.passolution.eu/en/oauth/authorize'),
            'token_endpoint' => env('PASSOLUTION_TOKEN_URL', 'https://web.passolution.eu/en/oauth/token'),
            'redirect_uri' => env('PASSOLUTION_REDIRECT_URI', 'https://api-client-oauth2-v2.passolution.de/oauth/callback'),
            'api_base_url' => env('PASSOLUTION_API_BASE_URL', 'https://api.passolution.eu/api/v2'),
            'scopes' => [], // Keine Standard-Scopes
        ],
        'google' => [
            'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_endpoint' => 'https://oauth2.googleapis.com/token',
            'scopes' => ['openid', 'profile', 'email'],
        ],
        'microsoft' => [
            'authorization_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'scopes' => ['openid', 'profile', 'email'],
        ],
        'github' => [
            'authorization_endpoint' => 'https://github.com/login/oauth/authorize',
            'token_endpoint' => 'https://github.com/login/oauth/access_token',
            'scopes' => ['user:email'],
        ],
    ],

    // Standard-Provider für die Anwendung
    'default_provider' => 'passolution',

    'security' => [
        'state_length' => 32,
        'code_verifier_length' => 128,
        'session_lifetime' => 120, // minutes
        'max_retries' => 3,
    ],

    'timeouts' => [
        'token_request' => 30, // seconds
        'authorization_timeout' => 600, // seconds
    ],

    'demo_mode' => env('OAUTH2_DEMO_MODE', true),

    // Passolution spezifische Einstellungen
    'passolution' => [
        'client_id' => env('PASSOLUTION_CLIENT_ID'),
        'client_secret' => env('PASSOLUTION_CLIENT_SECRET'),
        'default_scope' => '', // Kein Standard-Scope
        'api_version' => 'v2',
    ],
];