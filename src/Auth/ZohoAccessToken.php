<?php

namespace MelbaCh\LaravelZoho\Auth;

use InvalidArgumentException;
use League\OAuth2\Client\Token\AccessToken;

class ZohoAccessToken extends AccessToken
{
    /**
     * Constructs an access token.
     *
     * @param array $options An array of options returned by the service provider
     *                       in the access token request. The `access_token` option is required.
     *
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['expires_in_sec'])) {
            if (! is_numeric($options['expires_in_sec'])) {
                throw new \InvalidArgumentException('expires_in_sec value must be an integer');
            }

            $this->expires = $options['expires_in_sec'] !== 0 ? time() + $options['expires_in_sec'] : 0;
        }
    }

    public function setRefreshToken($refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}
