<?php

namespace MelbaCh\LaravelZoho\Tests\Repositories;

use Auth;
use Crypt;
use DB;
use Illuminate\Foundation\Auth\User;
use League\OAuth2\Client\Token\AccessTokenInterface;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Repositories\DatabaseAccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;

class DatabaseAccessTokenRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('guard')->andReturnSelf();
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(new User);
    }

    /** @test */
    public function it_know_the_access_token_exists()
    {
        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        DB::table('oauth_tokens')->insert([
            'provider'     => 'zoho',
            'owner_id'     => 1,
            'owner_type'   => (new User)->getMorphClass(),
            'access_token' => Crypt::encrypt($accessToken),
            'config'       => null,
        ]);

        $repository = app(DatabaseAccessTokenRepository::class);

        $this->assertTrue($repository->exists());
    }

    /** @test */
    public function it_know_the_access_token_does_not_exists()
    {
        $repository = app(DatabaseAccessTokenRepository::class);
        $this->assertFalse($repository->exists());
    }

    /** @test */
    public function it_returns_the_access_token_for_the_authenticated_user(): void
    {
        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        DB::table('oauth_tokens')->insert([
            'provider'     => 'zoho',
            'owner_id'     => 1,
            'owner_type'   => (new User)->getMorphClass(),
            'access_token' => Crypt::encrypt($accessToken),
            'config'       => null,
        ]);

        $repository = app(DatabaseAccessTokenRepository::class);

        $this->assertInstanceOf(AccessTokenInterface::class, $repository->get());
        $this->assertEquals($token, $repository->get()->getToken());
    }

    /** @test */
    public function it_store_the_access_token(): void
    {
        $token = uniqid('', true);
        $expectedAccessToken = new ZohoAccessToken(['access_token' => $token]);

        $repository = app(DatabaseAccessTokenRepository::class);

        $repository->store($expectedAccessToken);

        $record = DB::table('oauth_tokens')
            ->where('provider', 'zoho')
            ->where('owner_id', 1)
            ->where('owner_type', (new User)->getMorphClass())
            ->first();

        $accessToken = Crypt::decrypt($record->access_token);

        $this->assertInstanceOf(AccessTokenInterface::class, $accessToken);
        $this->assertEquals($expectedAccessToken, $accessToken);
    }

    /** @test */
    public function it_delete_the_access_token(): void
    {
        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        DB::table('oauth_tokens')->insert([
            'provider'     => 'zoho',
            'owner_id'     => 1,
            'owner_type'   => (new User)->getMorphClass(),
            'access_token' => Crypt::encrypt($accessToken),
            'config'       => null,
        ]);

        $repository = app(DatabaseAccessTokenRepository::class);
        $repository->delete();

        $this->assertDatabaseCount('oauth_tokens', 0);
        $this->assertNull($repository->get());
    }
}