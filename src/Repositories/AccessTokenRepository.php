<?php

namespace MelbaCh\LaravelZoho\Repositories;

use League\OAuth2\Client\Token\AccessTokenInterface;

interface AccessTokenRepository
{
    public function store(AccessTokenInterface $accessToken): self;

    public function get(): AccessTokenInterface|null;

    public function delete(): void;

    public function exists(): bool;
}
