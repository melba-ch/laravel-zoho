<?php

namespace MelbaCh\LaravelZoho\Tests;

use MelbaCh\LaravelZoho\ZohoResponse;
use GuzzleHttp\Psr7\Response as Psr7Response;

class ZohoResponseTest extends TestCase
{

    /** @test */
    public function it_see_errors_when_status_is_an_error(): void
    {
        $response = new ZohoResponse(new Psr7Response(400, [], null));
        $this->assertTrue($response->hasErrors());
    }

    /** @test */
    public function it_see_errors_in_response_body(): void
    {
        $body = [
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

        $response = new ZohoResponse(new Psr7Response(200, [], json_encode($body)));

        $this->assertTrue($response->hasErrors());
    }

    /** @test */
    public function it_returns_the_errors(): void
    {
        $body = [
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

        $response = new ZohoResponse(new Psr7Response(200, [], json_encode($body)));

        $this->assertTrue($response->hasErrors());
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
            $response->errors(),
        );
    }

}