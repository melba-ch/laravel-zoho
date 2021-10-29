<?php

namespace MelbaCh\LaravelZoho\Repositories;

class DefaultConfigRepository implements ConfigRepository
{
    protected array $config = [];

    public function __construct()
    {
        $this->config = [
            'secrets'    => [
                'client_id' => config('zoho.client_id'),
                'secret'    => config('zoho.secret'),
            ],
            'parameters' => [
                'region'                  => config('zoho.region') ?? 'US',
                'current_organization_id' => config('zoho.current_organization_id'),
                'scopes'                  => config('zoho.scopes', []),
            ],
        ];
    }

    public function get(): array
    {
        return $this->config;
    }

    public function region(): string
    {
        return $this->config['parameters']['region'];
    }

    public function scopes(): array
    {
        return $this->config['parameters']['scopes'];
    }

    public function secret(): string
    {
        return $this->config['secrets']['secret'];
    }

    public function clientId(): string
    {
        return $this->config['secrets']['client_id'];
    }

    public function currentOrganizationId(): ?int
    {
        return $this->config['parameters']['current_organization_id'];
    }

    public function store(array $config): ConfigRepository
    {
        throw new \Exception('`store` is not available when using DefaultConfigRepository');
    }

    public function delete(): void
    {
        throw new \Exception('`delete` is not available when using DefaultConfigRepository');
    }
}