<?php

namespace MelbaCh\LaravelZoho\Tests\Controllers;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Auth\ZohoAuthProvider;
use MelbaCh\LaravelZoho\Repositories\AccessTokenRepository;
use MelbaCh\LaravelZoho\Repositories\StorageConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use Mockery\MockInterface;

class ZohoAuthControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(StorageConfigRepository::class, static function (MockInterface $repository) {
            $repository->shouldReceive('clientId')->andReturn('abc-xyz');
            $repository->shouldReceive('secret')->andReturn('123-789');
            $repository->shouldReceive('region')->andReturn('EU');
            $repository->shouldReceive('scopes')->andReturn(['my-scope', 'my-another-scope']);
            $repository->shouldReceive('setScopes')->andReturnSelf();
        });
    }


    /** @test */
    public function it_redirect_the_user_to_zoho_when_code_is_not_provided(): void
    {
        $request = $this->get('oauth2/zoho');

        $url = 'https://accounts.zoho.eu/oauth/v2/auth';
        $parameters = [
            'redirect_uri' => 'http://localhost/oauth2/zoho',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => session('oauth2state'),
            'scope' => implode(',', config('zoho.scopes')),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'client_id' => 'abc-xyz',
        ];

        $query = http_build_query($parameters, null, '&', \PHP_QUERY_RFC3986);

        $request->assertRedirect($url . '?' . $query);
    }


    /** @test */
    public function it_store_the_state_before_redirecting_to_zoho(): void
    {
        $this->get('oauth2/zoho')
            ->assertRedirect();

        $this->assertNotNull(session('oauth2state'));
    }

    /** @test */
    public function it_returns_an_error_when_code_doesnt_match_state(): void
    {
        $this->mock(ZohoAuthProvider::class, static function (MockInterface $provider) {
            $token = uniqid('', true);
            $accessToken = new ZohoAccessToken(['access_token' => $token]);
            $provider->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $this->get('oauth2/zoho?code=error')
            ->assertForbidden();

        session(['oauth2state' => 'ok']);

        $this->get('oauth2/zoho?code=error')
            ->assertForbidden();

        session(['oauth2state' => 'ok']);

        $this->get('oauth2/zoho?code=error&state=not-ok')
            ->assertForbidden();

        session(['oauth2state' => 'ok']);

        $this->get('oauth2/zoho?code=ok&state=ok')
            ->assertRedirect(config('zoho.redirect_url'));
    }

    /** @test */
    public function it_save_the_access_token(): void
    {
        $accessToken = null;
        $this->mock(ZohoAuthProvider::class, static function (MockInterface $provider) use (&$accessToken) {
            $token = uniqid('', true);
            $accessToken = new ZohoAccessToken(['access_token' => $token]);
            $provider->shouldReceive('getAccessToken')->andReturn($accessToken);
        });

        $this->mock(AccessTokenRepository::class, static function (MockInterface $repository) use ($accessToken) {
            $repository->shouldReceive('store')->once()->with($accessToken);
        });

        session(['oauth2state' => 'ok']);
        $this->get('oauth2/zoho?code=ok&state=ok');
    }
}
