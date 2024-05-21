<?php

namespace MelbaCh\LaravelZoho\Tests\Middleware;

use Illuminate\Http\Request;
use MelbaCh\LaravelZoho\Middleware\VerifyZohoCredentialsDoesntExists;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyZohoCredentialsDoesntExistsTest extends TestCase
{
    private function createRequest($method, $uri): Request
    {
        $symfonyRequest = SymfonyRequest::create($uri, $method);

        return Request::createFromBase($symfonyRequest);
    }

    /** @test */
    public function it_abort_when_credentials_exists()
    {
        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) {
            $repository->shouldReceive('exists')->once()->andReturn(true);
        });

        $this->expectException(HttpException::class);

        // Run middleware
        app(VerifyZohoCredentialsDoesntExists::class)
            ->handle(
                $this->createRequest('get', '/'),
                fn () => new Response(),
            );
    }

    /** @test */
    public function it_allow_when_credentials_doesnt_exists()
    {
        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) {
            $repository->shouldReceive('exists')->once()->andReturn(false);
        });

        // Run middleware
        $response = app(VerifyZohoCredentialsDoesntExists::class)
            ->handle(
                $this->createRequest('get', '/'),
                fn () => new Response(),
            );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->isRedirect());
    }


}
