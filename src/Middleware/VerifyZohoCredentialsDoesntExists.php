<?php

namespace MelbaCh\LaravelZoho\Middleware;

use Closure;
use MelbaCh\LaravelZoho\Facades\ZohoHttp;

class VerifyZohoCredentialsDoesntExists
{
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
        if (ZohoHttp::hasAccessToken()) {
            abort(403);
        }
        return $next($request);
    }
}