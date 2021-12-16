<?php

return [
    /**
     * Routing
     */
    'url'          => '/oauth2/zoho',
    'redirect_url' => '/',

    // When requesting an accessToken, the API may return an error,
    // The controller will redirect the user to `on_error_url`
    // with the error flashed in the session with the key `zoho.access_token_error`
    // known error code:
    //      -  403: invalid_client_secret
    //      -  403: invalid_code
    //      -  500: fallback on unknown error
    'on_error_url' => '/',

    /**
     * Middleware to generate a Token
     */
    'middleware'   => [
        'web',
        \MelbaCh\LaravelZoho\Middleware\VerifyZohoCredentialsDoesntExists::class,
    ],

    'config_repository'       => \MelbaCh\LaravelZoho\Repositories\DefaultConfigRepository::class,
    /**
     * Specific to the Default config Repository
     */
    'client_id'               => env('ZOHO_CLIENT_ID'),
    'secret'                  => env('ZOHO_SECRET'),
    'region'                  => env('ZOHO_REGION', 'US'),
    'current_organization_id' => env('ZOHO_ORGANIZATION_ID'),
    'scopes'                  => [
        'ZohoBooks.settings.READ',
    ],

    'access_token_repository' => \MelbaCh\LaravelZoho\Repositories\DefaultAccessTokenRepository::class,
    /**
     * Specific to the Default Access Token Repository
     */
    'access_token_disk'       => 'local',
    'access_token_path'       => 'zoho/credentials',
];