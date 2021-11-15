<?php

namespace MelbaCh\LaravelZoho\Tests\Clients;

use Illuminate\Http\Client\Request;
use Http;
use Illuminate\Http\Client\Response;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Clients\ZohoHttp;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use MelbaCh\LaravelZoho\ZohoPendingRequest;
use MelbaCh\LaravelZoho\ZohoResponse;
use Mockery;
use Mockery\MockInterface;

class ZohoHttpTest extends TestCase
{
    protected ZohoHttp $zohoClientHttp;

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

        $this->zohoClientHttp = new ZohoHttp;
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

        $this->zohoClientHttp->fake([
            'users' => Http::response($fakeResponse),
            '*'     => Http::response(['error' => 'error']),
        ]);

        $this->assertEquals(
            $fakeResponse,
            $this->zohoClientHttp
                ->get('users')->json()

        );
    }


    /** @test */
    public function it_returns_a_zoho_response_class(): void
    {
        $this->zohoClientHttp->fake();

        $this->zohoClientHttp->get('/');

        /** @var Request $request */
        $request = $this->zohoClientHttp->recorded()[0][0];
        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertEquals('Zoho-oauthtoken mock_access_token', $request->headers()['Authorization'][0]);
    }

    /** @test */
    public function it_returns_a_zoho_pending_request(): void
    {
        $this->zohoClientHttp->fake();

        $pendingRequest = $this->zohoClientHttp->asForm();

        $reflection = new \ReflectionClass($pendingRequest);
        $reflectionProperty = $reflection->getProperty('options');
        $reflectionProperty->setAccessible(true);

        $options = $reflectionProperty->getValue($pendingRequest);

        $this->assertInstanceOf(ZohoPendingRequest::class, $pendingRequest);
        $this->assertEquals('Zoho-oauthtoken mock_access_token', $options['headers']['Authorization']);
    }

    /** @test */
    public function it_uses_the_zoho_bearer_header_when_performing_a_request(): void
    {
        $this->zohoClientHttp->fake();

        $this->assertInstanceOf(ZohoResponse::class, $this->zohoClientHttp->get('url'));
    }

    /** @test */
    public function it_prevent_refresh_token_call_when_using_fake(): void
    {
        $accessToken = $this->mock(ZohoAccessToken::class, static function (MockInterface $mock): void
        {
            $mock->shouldReceive('hasExpired')->never();
            $mock->shouldReceive('getRefreshToken')->never();
            $mock->shouldReceive('getToken')->once();
        });

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken)
        {
            $repository->shouldReceive('exists')->andReturn(true);
            $repository->shouldReceive('get')->andReturn($accessToken);
            $repository->shouldReceive('store');
        });

        $this->zohoClientHttp->fake();

        $this->zohoClientHttp->get('/');

        $this->assertInstanceOf(MockInterface::class, $accessToken);
    }

    /** @test */
    public function it_call_refresh_token_when_performing_a_request(): void
    {
        $accessToken = $this->mock(ZohoAccessToken::class, static function (MockInterface $mock): void
        {
            $mock->shouldReceive('hasExpired')->once()->andReturn(true);
            $mock->shouldReceive('getRefreshToken')->once()->andReturnSelf();
            $mock->shouldReceive('getToken')->once();
        });

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken)
        {
            $repository->shouldReceive('exists')->andReturn(true);
            $repository->shouldReceive('get')->andReturn($accessToken);
            $repository->shouldReceive('store');
        });

        $this->zohoClientHttp->fake();

        $reflection = new \ReflectionClass($this->zohoClientHttp);
        $reflectionProperty = $reflection->getProperty('isFaking');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->zohoClientHttp, false);

        $this->zohoClientHttp->get('/');

        $this->assertInstanceOf(MockInterface::class, $accessToken);
    }

    /** @test */
    public function it_call_only_once_refresh_token_when_performing_multiple_operations(): void
    {
        $accessToken = $this->mock(ZohoAccessToken::class, static function (MockInterface $mock): void
        {
            $mock->shouldReceive('hasExpired')->once()->andReturn(true);
            $mock->shouldReceive('getRefreshToken')->once()->andReturnSelf();
            $mock->shouldReceive('getToken')->once();
        });

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken)
        {
            $repository->shouldReceive('exists')->andReturn(true);
            $repository->shouldReceive('get')->andReturn($accessToken);
            $repository->shouldReceive('store');
        });

        $this->zohoClientHttp->fake();

        $reflection = new \ReflectionClass($this->zohoClientHttp);
        $reflectionProperty = $reflection->getProperty('isFaking');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->zohoClientHttp, false);

        $this->zohoClientHttp
            ->asForm()
            ->attach('file')
            ->get('/');

        $this->assertInstanceOf(MockInterface::class, $accessToken);
    }

}