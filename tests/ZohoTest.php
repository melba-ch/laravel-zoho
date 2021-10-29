<?php

namespace MelbaCh\LaravelZoho\Tests;

use Http;
use Illuminate\Http\Client\PendingRequest;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Facades\Zoho;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\DefaultConfigRepository;
use MelbaCh\LaravelZoho\ZohoModules;
use Mockery\MockInterface;

class ZohoTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $accessToken = new ZohoAccessToken(['access_token' => 'mock_access_token', 'expires' => now()->addHour()->timestamp]);

        $this->mock(DefaultConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('EU');
            $repository->shouldReceive('currentOrganizationId')->andReturn(1234);
        });

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
    }


    /** @test */
    public function it_can_make_get_request(): void
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

        Http::fake([
            'https://www.zohoapis.eu/crm/v2/users' => Http::response($fakeResponse),
            '*'                                    => Http::response([]),
        ]);

        $this->assertEquals(
            $fakeResponse,
            Zoho::get('/users')
        );
    }

    /** @test */
    public function it_can_make_post_request(): void
    {
        $fakeResponse = [
            "users" => [
                [
                    "code"    => "SUCCESS",
                    "details" => [
                        "id" => "554023000000691003",
                    ],
                    "message" => "User added",
                    "status"  => "success",
                ],
            ],
        ];

        Http::fake([
            'https://www.zohoapis.eu/crm/v2/users' => Http::response($fakeResponse),
            '*'                                    => Http::response([]),
        ]);

        $response = Zoho::post('/users', ['users' => [
            [
                'role'       => 554023000000015969,
                'first_name' => 'Patricia',
                'email'      => 'Patricia@abcl.com',
                'profile'    => '554023000000015975',
                'last_name'  => 'Boyle',
            ],
        ]]);

        $this->assertEquals(
            $fakeResponse,
            $response
        );
    }

    /** @test */
    public function it_can_make_put_request(): void
    {
        $fakeResponse = [
            "users" => [
                [
                    "code"    => "SUCCESS",
                    "details" => [
                        "id" => "554023000000691003",
                    ],
                    "message" => "User updated",
                    "status"  => "success",
                ],
            ],
        ];

        Http::fake([
            'https://www.zohoapis.eu/crm/v2/users' => Http::response($fakeResponse),
            '*'                                    => Http::response([]),
        ]);

        $response = Zoho::put('/users', ['users' => [
            [
                "id"             => "554023000000691003",
                "phone"          => "123456789",
                "email"          => "newtocrm@zoho.com",
                "dob"            => "1990-12-31",
                "role"           => "79234000000031154",
                "profile"        => "79234000000031157",
                "country_locale" => "en_US",
                "time_format"    => "HH:mm",
                "time_zone"      => "US/Samoa",
                "status"         => "active",
            ],
        ]]);

        $this->assertEquals(
            $fakeResponse,
            $response
        );

    }

    /** @test */
    public function it_can_make_delete_request(): void
    {
        $fakeResponse = [
            "users" => [
                [
                    "code"    => "SUCCESS",
                    "details" => [
                    ],
                    "message" => "User deleted",
                    "status"  => "success",
                ],
            ],
        ];

        Http::fake([
            'https://www.zohoapis.eu/crm/v2/users/554023000000691003' => Http::response($fakeResponse),
            '*'                                                       => Http::response([]),
        ]);

        $response = Zoho::delete('/users/554023000000691003');

        $this->assertEquals(
            $fakeResponse,
            $response
        );
    }

    /** @test */
    public function it_returns_the_http_client_with_the_headers(): void
    {
        $client = Zoho::clientHttp();

        $this->assertInstanceOf(PendingRequest::class, $client);
        $this->assertEquals(['Authorization' => 'Zoho-oauthtoken mock_access_token'], $client->mergeOptions()['headers']);
    }

    /** @test */
    public function it_register_the_errors_from_zoho(): void
    {
        $fakeResponse = [
            "users" => [
                [
                    "code"    => "SUCCESS",
                    "details" => [
                    ],
                    "message" => "User updated",
                    "status"  => "success",
                ],
                [
                    "code"    => "ERROR", // Maybe not an actual error
                    "details" => [
                    ],
                    "message" => "User doesn't not exists",
                    "status"  => "error",
                ],
                [
                    "code"    => "ERROR", // Maybe not an actual error
                    "details" => [
                    ],
                    "message" => "User doesn't not exists",
                    "status"  => "error",
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($fakeResponse),
        ]);

        Zoho::put('/users', []);

        $this->assertTrue(Zoho::hasErrors());
        $this->assertEquals(
            [
                [
                    "code"    => "ERROR", // Maybe not an actual error
                    "details" => [
                    ],
                    "message" => "User doesn't not exists",
                    "status"  => "error",
                ],
                [
                    "code"    => "ERROR", // Maybe not an actual error
                    "details" => [
                    ],
                    "message" => "User doesn't not exists",
                    "status"  => "error",
                ],
            ],
            Zoho::errors(),
        );
    }

    /** @test */
    public function it_can_request_using_different_modules(): void
    {
        Http::fake([
            'https://www.zohoapis.eu/crm/v2/users' => Http::response(['module' => 'crm']),
            'https://books.zoho.eu/api/v3/users*'  => Http::response(['module' => 'books']),
            '*'                                    => Http::response([]),
        ]);

        $this->assertEquals(
            ['module' => 'crm'],
            Zoho::setModule(ZohoModules::Crm)->get('/users')
        );

        $this->assertEquals(
            ['module' => 'books'],
            Zoho::setModule(ZohoModules::Books)->get('/users')
        );
    }

    /** @test */
    public function it_can_reset_the_errors(): void
    {
        $zoho = app(\MelbaCh\LaravelZoho\Zoho::class);

        $reflection = new \ReflectionProperty(get_class($zoho), 'errors');
        $reflection->setAccessible(true);
        $reflection->setValue($zoho, ['errors']);

        $this->assertTrue($zoho->hasErrors());
        $zoho->resetErrors();
        $this->assertFalse($zoho->hasErrors());
    }

}