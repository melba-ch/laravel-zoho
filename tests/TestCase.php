<?php

namespace MelbaCh\LaravelZoho\Tests;

use Illuminate\Encryption\Encrypter;
use MelbaCh\LaravelZoho\LaravelZohoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelZohoServiceProvider::class,
        ];
    }

    protected function setUpDatabase($app)
    {
        include_once(__DIR__.'/../database/migrations/create_oauth_tokens_table.php.stub');

        (new \CreateOauthTokensTable())->up();
    }


    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.key', Encrypter::generateKey('AES-256-CBC'));
        config()->set('database.default', 'testing');
    }
}
