<?php

namespace MelbaCh\LaravelZoho;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Factories\UrlFactory;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\ConfigRepository;

class Zoho
{
    public ZohoAuthProvider $provider;
    public AccessTokenRepository $accessTokenRepository;
    public ConfigRepository $configRepository;
    public UrlFactory $urlFactory;
    public string $module;
    public array $errors = [];

    public function __construct(
        ZohoAuthProvider      $provider,
        AccessTokenRepository $accessTokenRepository,
        ConfigRepository      $configRepository,
        UrlFactory            $urlFactory
    ) {
        $this->provider = $provider;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->configRepository = $configRepository;
        $this->urlFactory = $urlFactory;
        $this->module = config('zoho.default_module', ZohoModules::Crm);

        $this->refreshAccessToken();
    }

    public function setModule(string $module): self
    {
        $this->module = $module;
        return $this;
    }

    public function hasErrors(): bool
    {
        return count($this->errors());
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function resetErrors(): self
    {
        $this->errors = [];
        return $this;
    }

    public function clientHttp(): PendingRequest
    {
        return Http::withHeaders($this->headers());
    }

    public function get(string $url)
    {
        $response = $this->clientHttp()
            ->get($this->urlFactory->build($this->module, $url));

        $this->registerErrors($response);

        return $response->json();
    }

    public function post(string $url, array $data)
    {
        $response = $this->clientHttp()
            ->post($this->urlFactory->build($this->module, $url), $data);

        $this->registerErrors($response);

        return $response->json();
    }

    public function put(string $url, array $data)
    {
        $response = $this->clientHttp()
            ->put($this->urlFactory->build($this->module, $url), $data);

        $this->registerErrors($response);

        return $response->json();
    }

    public function delete(string $url)
    {
        $response = $this->clientHttp()
            ->delete($this->urlFactory->build($this->module, $url));

        $this->registerErrors($response);

        return $response->json();
    }

    public function headers(): array
    {
        $headers = [];
        if ($token = $this->accessTokenRepository->get()) {
            $headers['Authorization'] = "Zoho-oauthtoken {$token->getToken()}";
        }
        return $headers;
    }

    public function hasAccessToken(): bool
    {
        return $this->accessTokenRepository->exists();
    }

    protected function registerErrors(Response $response): void
    {
        $this->errors = collect($response->json())
            ->flatten(1)
            ->filter(function ($value)
            {
                if (is_array($value) && array_key_exists('code', $value)) {
                    return $value['code'] !== 'SUCCESS';
                }
                return null;
            })
            ->values()
            ->toArray();
    }

    private function refreshAccessToken()
    {
        $accessToken = $this->accessTokenRepository->get();

        if (! $accessToken) {
            return;
        }

        $refreshToken = $accessToken->getRefreshToken();

        if ($accessToken->hasExpired()) {
            $accessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            // Zoho doesn't return the refreshToken in the response. We have to re-set it afterward
            // https://help.zoho.com/portal/community/topic/refresh-token-missing
            $accessToken->setRefreshToken($refreshToken);

            $this->accessTokenRepository->store($accessToken);
        }
    }
}