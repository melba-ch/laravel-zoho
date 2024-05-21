<?php

namespace MelbaCh\LaravelZoho\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Middleware\RefreshZohoAuthToken;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\StorageAccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;

class RefreshZohoAuthTokenMiddlewareTest extends TestCase
{
    private function createRequest($method, $uri): Request
    {
        $symfonyRequest = SymfonyRequest::create($uri, $method);

        return Request::createFromBase($symfonyRequest);
    }

    /** @test */
    public function it_refresh_the_token_when_expired()
    {
        // Configuration
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token, 'expires_in_sec' => -1000]);

        $repository->store($accessToken);

        // Expectations
        $expectedToken = uniqid('', true);
        $expectedAccessToken = new ZohoAccessToken(['access_token' => $expectedToken]);
        $this->mock(ZohoAuthProvider::class, static function (MockInterface $provider) use ($expectedAccessToken) {
            $provider->shouldReceive('getAccessToken')->once()->andReturn($expectedAccessToken);
        });

        // Run middleware
        $response = app(RefreshZohoAuthToken::class)
            ->handle(
                $this->createRequest('get', '/'),
                fn () => new Response(),
            );

        // Assert
        $this->assertEquals($expectedToken, $repository->get()->getToken());
        $this->assertNotEquals($token, $repository->get()->getToken());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
    }

    /** @test */
    public function it_skips_when_there_is_no_token()
    {
        // Configuration
        Storage::fake(config('zoho.access_token_disk'));

        // Expectations
        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) {
            $repository->shouldReceive('get')->once()->andReturn(null);
            $repository->shouldNotReceive('store');
        });
        $this->mock(ZohoAuthProvider::class, static function (MockInterface $provider) {
            $provider->shouldNotReceive('getAccessToken');
        });

        // Run middleware
        $response = app(RefreshZohoAuthToken::class)
            ->handle(
                $this->createRequest('get', '/'),
                fn () => new Response(),
            );

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
    }

    /** @test */
    public function it_skips_when_token_is_not_expired()
    {
        // Configuration
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token, 'expires_in_sec' => +1000]);

        $repository->store($accessToken);

        // Expectations
        $this->mock(ZohoAuthProvider::class, static function (MockInterface $repository) {
            $repository->shouldNotReceive('getAccessToken');
        });

        // Run middleware
        $response = app(RefreshZohoAuthToken::class)
            ->handle(
                $this->createRequest('get', '/'),
                fn () => new Response(),
            );

        // Assert
        $this->assertEquals($token, $repository->get()->getToken());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
    }

}
