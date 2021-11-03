<?php

namespace MelbaCh\LaravelZoho\Clients;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\ZohoPendingRequest;
use MelbaCh\LaravelZoho\ZohoResponse;

class ZohoHttp extends Factory
{
    /**
     * @inheritDoc
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        // todo; prevent refresh when using ::fake()
        if (in_array($method, ['delete', 'get', 'head', 'patch', 'post', 'put', 'send'])) {
            // Refresh the access token if needed before performing a request
            $this->refreshAccessToken();
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
        if ($response instanceof PendingRequest) {
            return ZohoPendingRequest::fromPendingRequest($response);
        }

        return $response;
    }

    public function headers(): array
    {
        $headers = [];
        if ($token = app(AccessTokenRepository::class)->get()) {
            $headers['Authorization'] = "Zoho-oauthtoken {$token->getToken()}";
        }
        return $headers;
    }

    public function hasAccessToken(): bool
    {
        return app(AccessTokenRepository::class)->exists();
    }

    private function refreshAccessToken()
    {
        $provider = app(ZohoAuthProvider::class);
        $accessTokenRepository = app(AccessTokenRepository::class);

        $accessToken = $accessTokenRepository->get();

        if (! $accessToken) {
            return;
        }

        $refreshToken = $accessToken->getRefreshToken();

        if ($accessToken->hasExpired()) {
            $accessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            // Zoho doesn't return the refreshToken in the response. We have to re-set it afterward
            // https://help.zoho.com/portal/community/topic/refresh-token-missing
            $accessToken->setRefreshToken($refreshToken);

            $accessTokenRepository->store($accessToken);
        }
    }
}