<?php

namespace App\Services\TokenStore;

interface TokenStore
{
    public function get(): ?string;

    public function set(string $token): bool;

    public function delete(): bool;

    public function has(): bool;
}
