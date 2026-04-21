<?php

namespace App\Services\TokenStore;

use Native\Mobile\Facades\SecureStorage;

class SecureStorageTokenStore implements TokenStore
{
    public function __construct(protected string $key) {}

    public function get(): ?string
    {
        return SecureStorage::get($this->key);
    }

    public function set(string $token): bool
    {
        return SecureStorage::set($this->key, $token);
    }

    public function delete(): bool
    {
        return SecureStorage::delete($this->key);
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }
}
