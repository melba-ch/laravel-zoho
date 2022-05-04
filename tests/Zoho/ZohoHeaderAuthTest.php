<?php

namespace MelbaCh\LaravelZoho\Tests\Zoho;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use Mockery\MockInterface;

class ZohoHeaderAuthTest extends TestCase
{

    /** @test */
    public function it_adds_the_header(): void
    {
        $accessToken = new ZohoAccessToken(['access_token' => 'mock_access_token', 'expires' => now()->addHour()->timestamp]);
        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken) {
            $repository->shouldReceive('get')->andReturn($accessToken);
        });

        /** @var PendingRequest $http */
        $http = Http::withZohoHeader();
        $this->assertEquals(['Authorization' => 'Zoho-oauthtoken mock_access_token'], $http->getOptions()['headers']);
    }

    /** @test */
    public function it_doesnt_add_header_when_token_is_not_found(): void
    {
        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) {
            $repository->shouldReceive('get')->andReturn(null);
        });

        Http::fake();

        /** @var PendingRequest $http */
        $http = Http::withZohoHeader();
        $this->assertEquals([], $http->getOptions()['headers']);
    }


}