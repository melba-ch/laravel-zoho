<?php

namespace MelbaCh\LaravelZoho\Tests\Clients;

use MelbaCh\LaravelZoho\Clients\ZohoUrlFactory;
use MelbaCh\LaravelZoho\Repositories\DefaultConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use MelbaCh\LaravelZoho\ZohoModules;
use Mockery\MockInterface;


class ZohoUrlFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(DefaultConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('EU');
            $repository->shouldReceive('currentOrganizationId')->andReturn(1234);
        });
    }

    /** @test */
    public function it_build_url_for_a_module(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v2/users/4',
            $urlFactory->make(ZohoModules::Crm, '/users/4')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?organization_id=1234',
            $urlFactory->make(ZohoModules::Books, '/invoices')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2/users',
            $urlFactory->make(ZohoModules::Recruit, '/users')
        );
    }

    /** @test */
    public function it_returns_the_base_urls_for_the_api(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v2',
            $urlFactory->baseApiUrl(ZohoModules::Crm)
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3',
            $urlFactory->baseApiUrl(ZohoModules::Books)
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2',
            $urlFactory->baseApiUrl(ZohoModules::Recruit)
        );
    }

    /** @test */
    public function it_returns_the_urls_for_the_authentication(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://accounts.zoho.eu/oauth/v2/auth',
            $urlFactory->oauthApiUrl('authorization_url')
        );

        $this->assertEquals(
            'https://accounts.zoho.eu/oauth/v2/token',
            $urlFactory->oauthApiUrl('access_token_url')
        );

        $this->assertEquals(
            'https://accounts.zoho.eu/oauth/v2/token/revoke',
            $urlFactory->oauthApiUrl('revoke_access_token_url')
        );
    }

    /** @test */
    public function it_add_the_current_organization_id_to_url_when_using_books(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?organization_id=1234',
            $urlFactory->make(ZohoModules::Books, '/invoices')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?param_1=param&organization_id=1234',
            $urlFactory->make(ZohoModules::Books, '/invoices?param_1=param')
        );
        
    }

    /** @test */
    public function it_uses_the_region(): void
    {
        $this->mock(DefaultConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('EU');
        });
        $this->assertEquals(
            'https://books.zoho.eu/api/v3',
            app(ZohoUrlFactory::class)->baseApiUrl(ZohoModules::Books)
        );

        $this->mock(DefaultConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('US');
        });
        $this->assertEquals(
            'https://books.zoho.com/api/v3',
            app(ZohoUrlFactory::class)->baseApiUrl(ZohoModules::Books)
        );
    }

    /** @test */
    public function it_build_using_api_method(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v2/users/4',
            $urlFactory->api(ZohoModules::Crm, '/users/4')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?organization_id=1234',
            $urlFactory->api(ZohoModules::Books, '/invoices')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2/users',
            $urlFactory->api(ZohoModules::Recruit, '/users')
        );
    }


    /** @test */
    public function it_build_using_web_method(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://crm.zoho.eu/crm/1234/tab/Potentials/292528000000000000',
            $urlFactory->web(ZohoModules::Crm, '/tab/Potentials/292528000000000000')
        );

        $this->assertEquals(
            'https://books.zoho.eu/app#/contacts/139996000000000000',
            $urlFactory->web(ZohoModules::Books, '/contacts/139996000000000000')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/1234/EntityInfo.do?module=Candidates&id=31529000000000000&submodule=Candidates',
            $urlFactory->web(ZohoModules::Recruit, '/EntityInfo.do?module=Candidates&id=31529000000000000&submodule=Candidates')
        );
    }
}