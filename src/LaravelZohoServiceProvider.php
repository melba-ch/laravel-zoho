<?php

namespace MelbaCh\LaravelZoho;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Clients\ZohoClientFactory;
use MelbaCh\LaravelZoho\Clients\ZohoURLFactory;
use MelbaCh\LaravelZoho\Controllers\ZohoAuthController;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\ConfigRepository;
use MelbaCh\LaravelZoho\Repositories\DefaultAccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\DefaultConfigRepository;

class LaravelZohoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/zoho.php' => base_path('config/zoho.php'),
        ], 'laravel-zoho-config');

        if (! class_exists('CreateOauthTokensTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_oauth_tokens_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_oauth_tokens_table.php'),
            ], 'laravel-zoho-migrations');
        }

        Route::get(config('zoho.route', '/oauth2/zoho'), [ZohoAuthController::class, 'requestToken']);
    }

    public function register()
    {
        $this->app->bind(
            ConfigRepository::class,
            config('zoho.config_repository', DefaultConfigRepository::class)
        );

        $this->app->bind(
            AccessTokenRepository::class,
            config('zoho.access_token_repository', DefaultAccessTokenRepository::class)
        );

        $this->app->bind(ZohoAuthProvider::class, function ()
        {
            $config = app(ConfigRepository::class);
            return new ZohoAuthProvider([
                'clientSecret' => $config->secret(),
                'clientId'     => $config->clientId(),
            ]);
        });

        $this->app->bind(
            \MelbaCh\LaravelZoho\Facades\ZohoHttp::class,
            ZohoClientFactory::class
        );

        $this->app->bind(
            \MelbaCh\LaravelZoho\Facades\ZohoUrl::class,
            ZohoURLFactory::class
        );

        $this->mergeConfigFrom(__DIR__ . '/../config/zoho.php', 'zoho');
    }
}
