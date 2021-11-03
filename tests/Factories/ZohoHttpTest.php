<?php

namespace MelbaCh\LaravelZoho\Tests\Factories;

use Illuminate\Http\Client\Request;
use Http;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Clients\ZohoClientFactory;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use MelbaCh\LaravelZoho\ZohoResponse;
use Mockery\MockInterface;

class ZohoHttpTest extends TestCase
{
    protected ZohoClientFactory $zohoClientFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $accessToken = new ZohoAccessToken(['access_token' => 'mock_access_token', 'expires' => now()->addHour()->timestamp]);

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken)
        {
            $repository->shouldReceive('exists')->andReturn(true);
            $repository->shouldReceive('get')->andReturn($accessToken);
            $repository->shouldReceive('store')->with($accessToken);
        });

        $this->mock(ZohoAuthProvider::class, static function (MockInterface $repository) use ($accessToken)
        {
            $repository->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $this->zohoClientFactory = new ZohoClientFactory;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    /** @test */
    public function it_can_make_a_request(): void
    {
        $fakeResponse = [
            "users" => [
                [
                    "country"    => "US",
                    "street"     => null,
                    "id"         => "4150868000000225013",
                    "first_name" => "Patricia",
                    "last_name"  => "Boyle",
                ],
            ],
            "info"  => [
                "per_page"     => 200,
                "count"        => 3,
                "page"         => 1,
                "more_records" => false,
            ],
        ];

        $this->zohoClientFactory->fake([
            'users' => Http::response($fakeResponse),
            '*'     => Http::response(['error' => 'error']),
        ]);

        $this->assertEquals(
            $fakeResponse,
            $this->zohoClientFactory
                ->get('users')->json()

        );
    }


    /** @test */
    public function it_returns_a_zoho_response_class(): void
    {
        $this->zohoClientFactory->fake();

        $this->zohoClientFactory->get('/');

        /** @var Request $request */
        $request = $this->zohoClientFactory->recorded()[0][0];
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertEquals('Zoho-oauthtoken mock_access_token', $request->headers()['Authorization'][0]);
    }

    /** @test */
    public function it_uses_the_zoho_bearer_header_when_performing_a_request(): void
    {
        $this->zohoClientFactory->fake();

        $this->assertInstanceOf(ZohoResponse::class, $this->zohoClientFactory->get('url'));
    }
}