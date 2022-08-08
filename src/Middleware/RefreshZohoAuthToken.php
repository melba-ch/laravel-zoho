<?php

namespace MelbaCh\LaravelZoho\Middleware;

use Closure;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;

class RefreshZohoAuthToken
{
    public function __construct(
        private readonly ZohoAuthProvider $provider,
        private readonly AccessTokenRepository $accessTokenRepository
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $accessToken = $this->accessTokenRepository->get();

        if (!$accessToken) {
            return $next($request);
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

        return $next($request);
    }
}