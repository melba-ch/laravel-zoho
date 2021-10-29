<?php

namespace MelbaCh\LaravelZoho\Repositories;

use Crypt;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Token\AccessTokenInterface;

class DefaultAccessTokenRepository implements AccessTokenRepository
{

    public function store(AccessTokenInterface $accessToken): AccessTokenRepository
    {
        $hash = Crypt::encrypt($accessToken);
        Storage::disk(config('zoho.access_token_disk', null))
            ->put(config('zoho.access_token_path'), $hash);

        return $this;
    }

    public function get(): ?AccessTokenInterface
    {
        try {
            $hash = Storage::disk(config('zoho.access_token_disk', null))
                ->get(config('zoho.access_token_path'));

            return Crypt::decrypt($hash);

        } catch (FileNotFoundException $exception) {
            return null;
        }
    }

    public function delete(): void
    {
        Storage::disk(config('zoho.access_token_disk', null))
            ->delete(config('zoho.access_token_path'));
    }

    public function exists(): bool
    {
        return Storage::disk(config('zoho.access_token_disk', null))
            ->exists(config('zoho.access_token_path'));
    }
}