<?php

return [
    /**
     * Routing
     */
    'url'          => '/oauth2/zoho',
    'redirect_url' => '/',

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