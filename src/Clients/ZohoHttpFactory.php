<?php

namespace MelbaCh\LaravelZoho\Clients;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\ZohoPendingRequest;
use MelbaCh\LaravelZoho\ZohoResponse;

class ZohoHttpFactory extends Factory
{
    protected bool $isFaking = false;

    /**
     * @inheritDoc
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if ($this->isFaking === false) {
            $this->refreshAccessToken();
        }

        $response = tap($this->newPendingRequest(), function (PendingRequest $request)
        {
            $request
                ->withHeaders($this->headers())
                ->stub($this->stubCallbacks);
        })->{$method}(...$parameters);

        return $this->transformResponse($response);
    }

    private function transformResponse(
        Response|PendingRequest|array $response
    ): ZohoResponse|ZohoPendingRequest|array {
        if ($response instanceof Response) {
            return ZohoResponse::fromResponse($response);
        }
        if ($response instanceof PendingRequest) {
            return ZohoPendingRequest::fromPendingRequest($response);
        }

        if (is_array($response)) {
            return array_map(fn($res) => $this->transformResponse($res), $response);
        }
    }

    public function fake($callback = null)
    {
        $this->isFaking = true;
        return parent::fake($callback);
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

    private function refreshAccessToken(): void
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