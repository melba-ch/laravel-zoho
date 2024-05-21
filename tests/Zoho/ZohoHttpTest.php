<?php

namespace MelbaCh\LaravelZoho\Tests\Zoho;

use Illuminate\Support\Facades\Http;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Facades\ZohoUrl;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use MelbaCh\LaravelZoho\ZohoModules;
use Mockery\MockInterface;

class ZohoHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $accessToken = new ZohoAccessToken(['access_token' => 'mock_access_token', 'expires' => now()->addHour()->timestamp]);

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken) {
            $repository->shouldReceive('get')->andReturn($accessToken);
        });

        $this->mock(ZohoAuthProvider::class, static function (MockInterface $repository) use ($accessToken) {
            $repository->shouldReceive('getAccessToken')->andReturn($accessToken);
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    protected array $fakeResponse = [
        "users" => [
            [
                "country" => "US",
                "street" => null,
                "id" => "4150868000000225013",
                "first_name" => "Patricia",
                "last_name" => "Boyle",
            ],
        ],
        "info" => [
            "per_page" => 200,
            "count" => 3,
            "page" => 1,
            "more_records" => false,
        ],
    ];

    /** @test */
    public function it_can_make_a_request_using_header(): void
    {
        Http::fake([
            'users' => Http::response($this->fakeResponse),
        ]);

        $this->assertEquals(
            $this->fakeResponse,
            Http::withZohoHeader()->get('users')->json()
        );
    }

    /** @test */
    public function it_can_make_a_request_zoho_url_facade(): void
    {
        Http::fake([
            'https://www.zohoapis.com/crm/v3/users' => Http::response($this->fakeResponse),
        ]);

        $this->assertEquals(
            $this->fakeResponse,
            Http::withZohoHeader()
                ->get(ZohoUrl::api(ZohoModules::CRM, 'users'))
                ->json()
        );
    }


}
