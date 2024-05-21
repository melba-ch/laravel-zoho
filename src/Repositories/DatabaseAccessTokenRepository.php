<?php

namespace MelbaCh\LaravelZoho\Repositories;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use League\OAuth2\Client\Token\AccessTokenInterface;

class DatabaseAccessTokenRepository implements AccessTokenRepository
{
    protected const Provider = 'zoho';
    protected ?AccessTokenInterface $accessToken = null;

    public function store(AccessTokenInterface $accessToken): AccessTokenRepository
    {
        $this->accessToken = $accessToken;

        DB::table('oauth_tokens')->updateOrInsert([
            'owner_id' => $this->ownerId(),
            'owner_type' => $this->ownerType(),
            'provider' => self::Provider,
        ], ['access_token' => Crypt::encrypt($accessToken)]);

        return $this;
    }

    public function get(): AccessTokenInterface|null
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $record = DB::table('oauth_tokens')
            ->where('provider', self::Provider)
            ->where('owner_id', $this->ownerId())
            ->where('owner_type', $this->ownerType())
            ->first();

        if ($record && $record->access_token) {
            $this->accessToken = Crypt::decrypt($record->access_token);
        }

        return $this->accessToken;
    }

    public function delete(): void
    {
        $this->accessToken = null;

        DB::table('oauth_tokens')
            ->where('provider', self::Provider)
            ->where('owner_id', $this->ownerId())
            ->where('owner_type', $this->ownerType())
            ->delete();
    }

    public function exists(): bool
    {
        return $this->get() !== null;
    }

    protected function ownerId(): string|int|null
    {
        return Auth::guard(config('zoho.auth_guard'))?->id();
    }

    protected function ownerType(): string|null
    {
        /** @var Model $user */
        $user = Auth::guard(config('zoho.auth_guard'))->user();

        return $user?->getMorphClass();
    }
}
