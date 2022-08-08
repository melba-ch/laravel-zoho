<?php

namespace MelbaCh\LaravelZoho\Tests\Clients;

use MelbaCh\LaravelZoho\Clients\ZohoUrlFactory;
use MelbaCh\LaravelZoho\Repositories\StorageConfigRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;
use MelbaCh\LaravelZoho\ZohoModules;
use Mockery\MockInterface;


class ZohoUrlFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(StorageConfigRepository::class, static function (MockInterface $repository)
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
            'https://www.zohoapis.eu/crm/v3/users/4',
            $urlFactory->api(ZohoModules::CRM, '/users/4')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?organization_id=1234',
            $urlFactory->api(ZohoModules::BOOKS, '/invoices')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2/users',
            $urlFactory->api(ZohoModules::RECRUIT, '/users')
        );
    }

    /** @test */
    public function it_returns_the_base_urls_for_the_api(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v3',
            invade($urlFactory)->baseApiUrl(ZohoModules::CRM)
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3',
            invade($urlFactory)->baseApiUrl(ZohoModules::BOOKS)
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2',
            invade($urlFactory)->baseApiUrl(ZohoModules::RECRUIT)
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
            $urlFactory->api(ZohoModules::BOOKS, '/invoices')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?param_1=param&organization_id=1234',
            $urlFactory->api(ZohoModules::BOOKS, '/invoices?param_1=param')
        );
        
    }

    /** @test */
    public function it_uses_the_region(): void
    {
        $this->mock(StorageConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('EU');
        });
        $this->assertEquals(
            'https://books.zoho.eu/api/v3',
            invade(app(ZohoUrlFactory::class))->baseApiUrl(ZohoModules::BOOKS)
        );

        $this->mock(StorageConfigRepository::class, static function (MockInterface $repository)
        {
            $repository->shouldReceive('region')->andReturn('US');
        });
        $this->assertEquals(
            'https://books.zoho.com/api/v3',
            invade(app(ZohoUrlFactory::class))->baseApiUrl(ZohoModules::BOOKS)
        );
    }

    /** @test */
    public function it_build_using_api_method(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v3/users/4',
            $urlFactory->api(ZohoModules::CRM, '/users/4')
        );

        $this->assertEquals(
            'https://books.zoho.eu/api/v3/invoices?organization_id=1234',
            $urlFactory->api(ZohoModules::BOOKS, '/invoices')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/v2/users',
            $urlFactory->api(ZohoModules::RECRUIT, '/users')
        );
    }


    /** @test */
    public function it_build_using_web_method(): void
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://crm.zoho.eu/crm/1234/tab/Potentials/292528000000000000',
            $urlFactory->web(ZohoModules::CRM, '/tab/Potentials/292528000000000000')
        );

        $this->assertEquals(
            'https://books.zoho.eu/app#/contacts/139996000000000000',
            $urlFactory->web(ZohoModules::BOOKS, '/contacts/139996000000000000')
        );

        $this->assertEquals(
            'https://recruit.zoho.eu/recruit/1234/EntityInfo.do?module=Candidates&id=31529000000000000&submodule=Candidates',
            $urlFactory->web(ZohoModules::RECRUIT, '/EntityInfo.do?module=Candidates&id=31529000000000000&submodule=Candidates')
        );
    }

    /** @test */
    public function it_adds_parameter_to_api_url()
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v3/users/4?foo=1&bar=2&baz=3',
            $urlFactory->api(ZohoModules::CRM, '/users/4', [
                'foo' => 1,
                'bar' => 2,
                'baz' => 3,
            ])
        );

        $this->assertEquals(
            'https://www.zohoapis.eu/crm/v3/users/4?foo=1&bar=2&baz=3',
            $urlFactory->api(ZohoModules::CRM, '/users/4?foo=1', [
                'bar' => 2,
                'baz' => 3,
            ])
        );
    }

    /** @test */
    public function it_adds_parameter_to_web_url()
    {
        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://crm.zoho.eu/crm/1234/users/4?foo=1&bar=2&baz=3',
            $urlFactory->web(ZohoModules::CRM, '/users/4', [
                'foo' => 1,
                'bar' => 2,
                'baz' => 3,
            ])
        );

        $this->assertEquals(
            'https://crm.zoho.eu/crm/1234/users/4?foo=1&bar=2&baz=3',
            $urlFactory->web(ZohoModules::CRM, '/users/4?foo=1', [
                'bar' => 2,
                'baz' => 3,
            ])
        );
    }

    /** @test */
    public function it_uses_the_sandbox_for_web()
    {
        config(['zoho.sandbox' => true]);

        $urlFactory = app(ZohoUrlFactory::class);

        $this->assertEquals(
            'https://crmsandbox.zoho.eu/crm/1234/tab/Potentials/292528000000000000',
            $urlFactory->web(ZohoModules::CRM, '/tab/Potentials/292528000000000000')
        );

    }
}