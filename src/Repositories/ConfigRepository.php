<?php

namespace MelbaCh\LaravelZoho\Repositories;

interface ConfigRepository
{
    public function store(array $config): self;

    public function get(): array;

    public function delete(): void;

    public function region(): string;

    public function scopes(): array;

    public function secret(): string;

    public function clientId(): string;

    public function currentOrganizationId(): ?int;
}