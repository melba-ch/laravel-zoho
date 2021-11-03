<?php

namespace MelbaCh\LaravelZoho\Auth;

use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MelbaCh\LaravelZoho\Clients\ZohoURLFactory;
use MelbaCh\LaravelZoho\Repositories\ConfigRepository;
use Psr\Http\Message\ResponseInterface;

class ZohoAuthProvider extends AbstractProvider
{
    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
    {
        return app(ZohoURLFactory::class)->oauthApiUrl('authorization_url');
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return app(ZohoURLFactory::class)->oauthApiUrl('access_token_url');
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return '';
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes(): array
    {
        return app(ConfigRepository::class)->scopes();
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param array $response
     * @param AbstractGrant $grant
     *
     * @return AccessTokenInterface
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new ZohoAccessToken($response);
    }

    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array|string $data Parsed response data
     *
     * @return void
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            /*
             * todo: see if zoho throw another type of error messages
             * $data = [
             *     "code" => "INVALID_URL_PATTERN"
             *     "details" => []
             *     "message" => "Please check if the URL trying to access is a correct one"
             *     "status" => "error"
             * ]
             *
             */
            //\Log::error(json_encode($data));
            throw new IdentityProviderException(
                sprintf('There was an error on response: %s', $data['code']),
                $response->getStatusCode(),
                $data['message']
            );
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new ZohoResourceOwner($response);
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * Typically this is "Bearer" or "MAC". For more information see:
     * http://tools.ietf.org/html/rfc6749#section-7.1
     *
     * No default is provided, providers must overload this method to activate
     * authorization headers.
     *
     * @param mixed|null $token Either a string or an access token instance
     *
     * @return array
     */
    protected function getAuthorizationHeaders($token = null): array
    {
        return ['Authorization' => "Zoho-oauthtoken {$token}"];
    }
}