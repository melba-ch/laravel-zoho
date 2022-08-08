<?php

namespace MelbaCh\LaravelZoho\Tests\Repositories;

use Crypt;
use DB;
use Illuminate\Foundation\Auth\User;
use MelbaCh\LaravelZoho\Repositories\DatabaseConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;

class DatabaseConfigRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Auth::shouldReceive('guard')->andReturnSelf();
        \Auth::shouldReceive('id')->andReturn(1);
        \Auth::shouldReceive('user')->andReturn(new User);
    }

    protected function defaultConfig(): array
    {
        return [
            'secrets'    => [
                'client_id' => 'abc-xyz',
                'secret'    => '123-789',
            ],
            'parameters' => [
                'region'                  => 'EU',
                'current_organization_id' => 1234,
                'scopes'                  => ['my-scope', 'my-another-scope'],
            ],
        ];
    }

    protected function storeDefaultConfig()
    {
        DB::table('oauth_tokens')->insert([
            'provider'     => 'zoho',
            'owner_id'     => 1,
            'owner_type'   => (new User)->getMorphClass(),
            'access_token' => 'token',
            'config'       => Crypt::encrypt($this->defaultConfig()),
        ]);
    }

    /** @test */
    public function it_returns_the_config_for_the_authenticated_user(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals($this->defaultConfig(), $repository->get(),);
    }

    /** @test */
    public function it_store_the_config(): void
    {
        $repository = app(DatabaseConfigRepository::class);
        $this->assertEquals([], $repository->get());

        $repository->store($this->defaultConfig());

        $this->assertEquals($this->defaultConfig(), $repository->get());

        $reflection = new \ReflectionProperty(get_class($repository), 'config');
        $reflection->setAccessible(true);
        $reflection->setValue($repository, []);

        $this->assertEquals($this->defaultConfig(), $repository->get());
    }

    /** @test */
    public function it_delete_the_config(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $repository->delete();

        $this->assertEquals([], $repository->get());
    }


    /** @test */
    public function it_returns_the_region(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals('EU', $repository->region());
    }

    /** @test */
    public function it_set_the_scopes(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $repository->setScopes(['my-new-scope', 'another-new-scope']);

        $this->assertEquals(['my-new-scope', 'another-new-scope'], $repository->scopes());
    }

    /** @test */
    public function it_returns_the_scopes(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals(['my-scope', 'my-another-scope'], $repository->scopes());
    }

    /** @test */
    public function it_returns_the_secret(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals('123-789', $repository->secret());
    }

    /** @test */
    public function it_returns_the_client_id(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals('abc-xyz', $repository->clientId());
    }

    /** @test */
    public function it_returns_the_current_organization_id(): void
    {
        $repository = app(DatabaseConfigRepository::class);

        $this->storeDefaultConfig();

        $this->assertEquals(1234, $repository->currentOrganizationId());
    }

}