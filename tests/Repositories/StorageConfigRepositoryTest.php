<?php

namespace MelbaCh\LaravelZoho\Tests\Repositories;

use MelbaCh\LaravelZoho\Repositories\StorageConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;

class StorageConfigRepositoryTest extends TestCase
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
        $repository = app(StorageConfigRepository::class);

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
        $repository = app(StorageConfigRepository::class);
        $this->assertEquals('EU', $repository->region());
    }

    /** @test */
    public function it_cannot_set_the_scopes(): void
    {
        $repository = app(StorageConfigRepository::class);

        // Not supported
        $this->expectException(\Exception::class);
        $repository->setScopes(['my-new-scope', 'another-new-scope']);
    }

    /** @test */
    public function it_returns_the_scopes(): void
    {
        $repository = app(StorageConfigRepository::class);
        $this->assertEquals(['my-scope', 'my-another-scope'], $repository->scopes());
    }

    /** @test */
    public function it_returns_the_secret(): void
    {
        $repository = app(StorageConfigRepository::class);
        $this->assertEquals('123-789', $repository->secret());
    }

    /** @test */
    public function it_returns_the_client_id(): void
    {
        $repository = app(StorageConfigRepository::class);
        $this->assertEquals('abc-xyz', $repository->clientId());
    }

    /** @test */
    public function it_returns_the_current_organization_id(): void
    {
        $repository = app(StorageConfigRepository::class);
        $this->assertEquals(1234, $repository->currentOrganizationId());
    }

    /** @test */
    public function it_cannot_store(): void
    {
        $repository = app(StorageConfigRepository::class);

        // Not supported
        $this->expectException(\Exception::class);
        $repository->store([]);
    }

    /** @test */
    public function it_cannot_be_deleted(): void
    {
        $repository = app(StorageConfigRepository::class);

        // Not supported
        $this->expectException(\Exception::class);
        $repository->delete();
    }


}