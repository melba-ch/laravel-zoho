<?php

namespace MelbaCh\LaravelZoho\Repositories;

interface ConfigRepository
{
    public function store(array $config): self;

    public function get(): array;

    public function delete(): void;

    public function region(): string;

    public function scopes(): array;

    public function setScopes(array $scopes): self;

    public function secret(): string|null;

    public function clientId(): string|null;

    public function currentOrganizationId(): string|null;
}
