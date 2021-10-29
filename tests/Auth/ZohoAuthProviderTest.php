<?php

namespace MelbaCh\LaravelZoho\Tests\Auth;

use Exception;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Tests\TestCase;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;

class ZohoAuthProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ZohoAuthProvider([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
        ]);
    }

    /** @test */
    public function it_has_a_valid_authorization_url(): void
    {
        $url = $this->provider->getAuthorizationUrl();

        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    /** @test */
    public function it_has_a_valid_authorization_url_path(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertEquals('/oauth/v2/auth', $uri['path']);
    }

    /** @test */
    public function it_has_a_valid_access_token_url_path(): void
    {
        $params = [];
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/oauth/v2/token', $uri['path']);
    }

    /** @test */
    public function it_can_get_the_access_token_on_zoho(): void
    {
        $response = $this->mock(ResponseInterface::class, static function (MockInterface $response)
        {
            $data = [
                'access_token' => 'mock_access_token',
                'token_type'   => 'bearer',
            ];

            $response->shouldReceive('getBody')->andReturn(json_encode($data));
            $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
            $response->shouldReceive('getStatusCode')->andReturn(200);
        });

        $client = $this->mock(
            ClientInterface::class,
            static function (MockInterface $client) use ($response)
            {
                $client->shouldReceive('send')->times(1)->andReturn($response);
            });

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_returns_the_crm_owner_data(): void
    {
        $data = [
            'org' => [
                [
                    'id'           => $orgId = random_int(1000, 9999),
                    'company_name' => $orgName = uniqid('', true),
                ],
            ],
        ];

        $tokenResponse = $this->mock(
            ResponseInterface::class,
            static function (MockInterface $response)
            {
                $response->shouldReceive('getBody')
                    ->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token');
                $response->shouldReceive('getHeader')
                    ->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
                $response->shouldReceive('getStatusCode')
                    ->andReturn(200);
            });

        $ownerResponse = $this->mock(
            ResponseInterface::class,
            static function (MockInterface $response) use ($data)
            {
                $response->shouldReceive('getBody')->andReturn(json_encode($data));
                $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
                $response->shouldReceive('getStatusCode')->andReturn(200);
            });

        $client = $this->mock(
            ClientInterface::class,
            static function (MockInterface $client) use ($tokenResponse, $ownerResponse)
            {
                $client->shouldReceive('send')
                    ->times(2)
                    ->andReturn($tokenResponse, $ownerResponse);
            });

        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);
        $this->assertEquals($orgId, $user->getId());
        $this->assertEquals($orgName, $user->getName());
        $this->assertEquals($orgId, $user->toArray()['id']);
    }

    /**
     * @test
     * @throws Exception
     */
    public function exception_is_thrown_when_error_object_is_received(): void
    {
        $status = random_int(400, 600);
        $error = [
            'message' => uniqid('', true),
            'code'    => uniqid('', true),
        ];

        $response = $this->mock(ResponseInterface::class,
            static function (MockInterface $response) use ($status, $error)
            {
                $response->shouldReceive('getBody')->andReturn(json_encode($error));
                $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
                $response->shouldReceive('getStatusCode')->andReturn($status);
            });

        $client = $this->mock(ClientInterface::class, static function (MockInterface $client) use ($response)
        {
            $client->shouldReceive('send')
                ->times(1)
                ->andReturn($response);
        });

        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}