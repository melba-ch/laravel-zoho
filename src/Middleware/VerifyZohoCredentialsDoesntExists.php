<?php

namespace MelbaCh\LaravelZoho\Middleware;

use Closure;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;

class VerifyZohoCredentialsDoesntExists
{
    public function __construct(
        private readonly AccessTokenRepository $accessTokenRepository
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->accessTokenRepository->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
