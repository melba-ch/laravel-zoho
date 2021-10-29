<?php

namespace MelbaCh\LaravelZoho\Tests\Repositories;

use MelbaCh\LaravelZoho\Repositories\DefaultConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;

class DefaultConfigRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('zoho.client_id', 'abc-xyz');
        config()->set('zoho.secret', '123-789');
        config()->set('zoho.region', 'EU');
        config()->set('zoho.current_organization_id', 1234);
        config()->set('zoho.scopes', ['my-scope', 'my-another-scope']);
    }

    /** @test */
    public function it_returns_the_config(): void
    {
        $repository = app(DefaultConfigRepository::class);

        $this->assertEquals(
            [
                'secrets'    => [
                    'client_id' => 'abc-xyz',
                    'secret'    => '123-789',
                ],
                'parameters' => [
                    'region'                  => 'EU',
                    'current_organization_id' => 1234,
                    'scopes'                  => ['my-scope', 'my-another-scope'],
                ],
            ],
            $repository->get(),
        );
    }


    /** @test */
    public function it_returns_the_region(): void
    {
        $repository = app(DefaultConfigRepository::class);
        $this->assertEquals('EU', $repository->region());
    }

    /** @test */
    public function it_returns_the_scopes(): void
    {
        $repository = app(DefaultConfigRepository::class);
        $this->assertEquals(['my-scope', 'my-another-scope'], $repository->scopes());
    }

    /** @test */
    public function it_returns_the_secret(): void
    {
        $repository = app(DefaultConfigRepository::class);
        $this->assertEquals('123-789', $repository->secret());
    }

    /** @test */
    public function it_returns_the_client_id(): void
    {
        $repository = app(DefaultConfigRepository::class);
        $this->assertEquals('abc-xyz', $repository->clientId());
    }

    /** @test */
    public function it_returns_the_current_organization_id(): void
    {
        $repository = app(DefaultConfigRepository::class);
        $this->assertEquals(1234, $repository->currentOrganizationId());
    }

}