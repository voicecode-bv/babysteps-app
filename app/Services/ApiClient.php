<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Native\Mobile\Facades\SecureStorage;

class ApiClient
{
    public function isAppPlatform(): bool
    {
        return config('api-client.platform') === 'app';
    }

    public function getToken(): ?string
    {
        if ($this->isAppPlatform()) {
            return SecureStorage::get(config('api-client.token_key'));
        }

        return Session::get(config('api-client.token_key'));
    }

    public function storeToken(string $token): bool
    {
        if ($this->isAppPlatform()) {
            return SecureStorage::set(config('api-client.token_key'), $token);
        }

        Session::put(config('api-client.token_key'), $token);

        return true;
    }

    public function clearToken(): bool
    {
        if ($this->isAppPlatform()) {
            return SecureStorage::delete(config('api-client.token_key'));
        }

        Session::forget(config('api-client.token_key'));

        return true;
    }

    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    /**
     * @return array{success: bool, user?: array<string, mixed>, message?: string}
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->request()
                ->post('/auth/login', [
                    'email' => $email,
                    'password' => $password,
                    'device_name' => 'babysteps-mobile',
                ]);
        } catch (ConnectionException) {
            return ['success' => false, 'message' => __('Could not connect to the server')];
        }

        if ($response->successful()) {
            $data = $response->json();
            $this->storeToken($data['token']);

            return ['success' => true, 'user' => $data['user']];
        }

        return [
            'success' => false,
            'message' => $response->json('message', __('Invalid credentials')),
        ];
    }

    /**
     * @return array{success: bool, user?: array<string, mixed>, errors?: array<string, mixed>, message?: string}
     */
    public function register(string $name, string $username, string $email, string $password): array
    {
        try {
            $response = $this->request()
                ->post('/auth/register', [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'password_confirmation' => $password,
                    'device_name' => 'babysteps-mobile',
                ]);
        } catch (ConnectionException) {
            return ['success' => false, 'message' => __('Could not connect to the server')];
        }

        if ($response->successful()) {
            $data = $response->json();
            $this->storeToken($data['token']);

            return ['success' => true, 'user' => $data['user']];
        }

        if ($response->status() === 422) {
            return [
                'success' => false,
                'errors' => $response->json('errors', []),
                'message' => $response->json('message', __('Validation failed')),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message', __('Registration failed')),
        ];
    }

    /**
     * @return array{valid: bool, user?: array<string, mixed>}
     */
    public function validateToken(): array
    {
        if (! $this->hasToken()) {
            return ['valid' => false];
        }

        try {
            $response = $this->authenticated()->get('/auth/me');
        } catch (ConnectionException) {
            return ['valid' => false];
        }

        if ($response->successful()) {
            return ['valid' => true, 'user' => $response->json('user')];
        }

        $this->clearToken();

        return ['valid' => false];
    }

    public function logout(): void
    {
        if ($this->hasToken()) {
            $this->authenticated()->post('/auth/logout');
        }

        $this->clearToken();
    }

    public function get(string $path): Response
    {
        return $this->authenticated()->get($path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(string $path, array $data = []): Response
    {
        return $this->authenticated()->post($path, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function put(string $path, array $data = []): Response
    {
        return $this->authenticated()->put($path, $data);
    }

    public function delete(string $path): Response
    {
        return $this->authenticated()->delete($path);
    }

    public function request(): PendingRequest
    {
        $request = Http::baseUrl(config('api-client.base_url'))
            ->timeout(config('api-client.timeout'))
            ->acceptJson();

        if (app()->isLocal()) {
            $request->withoutVerifying();
        }

        return $request;
    }

    public function authenticated(): PendingRequest
    {
        return $this->request()->withToken($this->getToken());
    }
}
