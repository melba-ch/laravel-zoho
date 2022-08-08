<?php

namespace MelbaCh\LaravelZoho\Repositories;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DatabaseConfigRepository implements ConfigRepository
{
    protected const Provider = 'zoho';
    protected array $config = [];

    public function get(): array
    {
        if (count($this->config)) {
            return $this->config;
        }

        $record = DB::table('oauth_tokens')
            ->where('provider', self::Provider)
            ->where('owner_id', $this->ownerId())
            ->where('owner_type', $this->ownerType())
            ->first();

        if ($record && $record->config) {
            $this->config = Crypt::decrypt($record->config);
        }

        return $this->config;
    }

    public function region(): string
    {
        return $this->get()['parameters']['region'] ?? config('zoho.region', 'US');
    }

    public function scopes(): array
    {
        return $this->get()['parameters']['scopes'] ?? [];
    }

    public function setScopes(array $scopes): self
    {
        $config = $this->get();
        $config['parameters']['scopes'] = $scopes;
        $this->store($config);
        return $this;
    }

    public function secret(): string|null
    {
        return $this->get()['secrets']['secret'] ?? null;
    }

    public function clientId(): string|null
    {
        return $this->get()['secrets']['client_id'] ?? null;
    }

    public function currentOrganizationId(): string|null
    {
        return $this->get()['parameters']['current_organization_id'] ?? null;
    }

    public function store(array $config): ConfigRepository
    {
        $this->config = $config;

        DB::table('oauth_tokens')
            ->updateOrInsert([
                'provider'   => self::Provider,
                'owner_id'   => $this->ownerId(),
                'owner_type' => $this->ownerType(),
            ], [
                'config' => Crypt::encrypt($config),
            ]);

        return $this;
    }

    public function delete(): void
    {
        $this->config = [];
        DB::table('oauth_tokens')
            ->where('provider', self::Provider)
            ->where('owner_id', $this->ownerId())
            ->where('owner_type', $this->ownerType())
            ->update([
                'config' => null,
            ]);
    }

    protected function ownerId(): int|string|null
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