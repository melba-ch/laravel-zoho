<?php

namespace MelbaCh\LaravelZoho\Tests\Repositories;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use MelbaCh\LaravelZoho\Auth\ZohoAccessToken;
use MelbaCh\LaravelZoho\Repositories\StorageAccessTokenRepository;
use MelbaCh\LaravelZoho\Tests\TestCase;

class StorageAccessTokenRepositoryTest extends TestCase
{

    /** @test */
    public function it_store_the_token(): void
    {
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        $repository->store($accessToken);

        Storage::disk(config('zoho.access_token_disk'))->assertExists(config('zoho.access_token_path'));
        $credentials = Storage::disk(config('zoho.access_token_disk'))->get(config('zoho.access_token_path'));
        $credentials = Crypt::decrypt($credentials);

        $this->assertInstanceOf(ZohoAccessToken::class, $credentials);
        $this->assertEquals($token, $credentials->getToken());
    }

    /** @test */
    public function it_get_the_token(): void
    {
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        $this->assertNull($repository->get());

        Storage::disk(config('zoho.access_token_disk'))->put(config('zoho.access_token_path'), Crypt::encrypt($accessToken));

        $credentials = $repository->get();

        $this->assertInstanceOf(ZohoAccessToken::class, $credentials);
        $this->assertEquals($token, $credentials->getToken());
    }

    /** @test */
    public function it_can_delete_the_token(): void
    {
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        Storage::disk(config('zoho.access_token_disk'))->put(config('zoho.access_token_path'), Crypt::encrypt($accessToken));

        $this->assertFileExists(
            Storage::disk(config('zoho.access_token_disk'))->path(config('zoho.access_token_path'))
        );

        $repository->delete();

        $this->assertFileDoesNotExist(
            Storage::disk(config('zoho.access_token_disk'))->path(config('zoho.access_token_path'))
        );

        $this->assertNull($repository->get());
    }

    /** @test */
    public function it_can_verify_the_token_exists(): void
    {
        Storage::fake(config('zoho.access_token_disk'));
        $repository = app(StorageAccessTokenRepository::class);

        $token = uniqid('', true);
        $accessToken = new ZohoAccessToken(['access_token' => $token]);

        $this->assertFalse($repository->exists());

        $repository->store($accessToken);

        $this->assertTrue($repository->exists());
    }

}