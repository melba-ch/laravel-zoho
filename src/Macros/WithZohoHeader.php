<?php

namespace MelbaCh\LaravelZoho\Macros;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;

/**
 *
 * @mixin \Illuminate\Http\Client\PendingRequest
 * @return \Illuminate\Http\Client\PendingRequest
 */
class WithZohoHeader
{
    public function __invoke()
    {
        return function (): PendingRequest {
            $accessTokenRepository = app(AccessTokenRepository::class);
            $headers = [];
            if ($token = $accessTokenRepository->get()) {
                $headers['Authorization'] = "Zoho-oauthtoken {$token->getToken()}";
            }

            return Http::withHeaders($headers);
        };
    }
}
