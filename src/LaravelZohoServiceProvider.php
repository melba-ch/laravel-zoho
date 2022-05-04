<?php

namespace MelbaCh\LaravelZoho;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Controllers\ZohoAuthController;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\ConfigRepository;
use MelbaCh\LaravelZoho\Repositories\StorageAccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\StorageConfigRepository;

class LaravelZohoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/zoho.php' => base_path('config/zoho.php'),
        ], 'laravel-zoho-config');

        if (!class_exists('CreateOauthTokensTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_oauth_tokens_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_oauth_tokens_table.php'),
            ], 'laravel-zoho-migrations');
        }

        Http::macro('withZohoHeader', function () {
            $accessTokenRepository = app(AccessTokenRepository::class);
            $headers = [];
            if ($token = $accessTokenRepository->get()) {
                $headers['Authorization'] = "Zoho-oauthtoken {$token->getToken()}";
            }
            return Http::withHeaders($headers);
        });

        Response::macro('hasErrorsFromZoho', function () {
            return $this->status() >= 400 || count($this->errorsFromZoho());
        });

        Response::macro('errorsFromZoho', function () {
            if ($this->status() >= 400) {
                return $this->json() ?? [];
            }

            return collect($this->json())
                ->flatten(1)
                ->filter(function ($value) {
                    if (is_array($value) && array_key_exists('status', $value)) {
                        return $value['status'] === 'error';
                    }
                    return null;
                })
                ->values()
                ->toArray();
        });

        Route::get(config('zoho.url', '/oauth2/zoho'), [ZohoAuthController::class, 'requestToken']);
    }

    public function register()
    {
        $this->app->bind(
            ConfigRepository::class,
            config('zoho.config_repository', StorageConfigRepository::class)
        );

        $this->app->bind(
            AccessTokenRepository::class,
            config('zoho.access_token_repository', StorageAccessTokenRepository::class)
        );

        $this->app->bind(ZohoAuthProvider::class, function () {
            $config = app(ConfigRepository::class);
            return new ZohoAuthProvider([
                'clientSecret' => $config->secret(),
                'clientId'     => $config->clientId(),
            ]);
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/zoho.php', 'zoho');
    }
}
