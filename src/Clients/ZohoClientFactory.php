<?php

namespace MelbaCh\LaravelZoho\Clients;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\ZohoResponse;

class ZohoClientFactory extends Factory
{
    public ZohoAuthProvider $provider;
    public AccessTokenRepository $accessTokenRepository;

    public function __construct(
        Dispatcher $dispatcher = null
    ) {
        parent::__construct($dispatcher);

        $this->provider = app(ZohoAuthProvider::class);
        $this->accessTokenRepository = app(AccessTokenRepository::class);
        $this->refreshAccessToken();
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        $response = tap($this->newPendingRequest(), function (PendingRequest $request)
        {
            $request
                ->withHeaders($this->headers())
                ->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
        if ($response instanceof Response) {
            return ZohoResponse::fromResponse($response);
        }
        return $response;
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