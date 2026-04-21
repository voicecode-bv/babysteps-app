<?php

namespace App\Services\TokenStore;

use Illuminate\Contracts\Session\Session;

class SessionTokenStore implements TokenStore
{
    public function __construct(protected Session $session, protected string $key) {}

    public function get(): ?string
    {
        $value = $this->session->get($this->key);

        return is_string($value) ? $value : null;
    }

    public function set(string $token): bool
    {
        $this->session->put($this->key, $token);

        return true;
    }

    public function delete(): bool
    {
        $this->session->forget($this->key);

        return true;
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }
}
