<?php

namespace Babysteps\ApiClient\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\SecureStorage;

class ApiClient
{
    public function getToken(): ?string
    {
        return SecureStorage::get(config('api-client.token_key'));
    }

    public function storeToken(string $token): bool
    {
        return SecureStorage::set(config('api-client.token_key'), $token);
    }

    public function clearToken(): bool
    {
        return SecureStorage::delete(config('api-client.token_key'));
    }

    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    /**
     * Authenticate with the backend and store the received token.
     *
     * @return array{success: bool, user?: array<string, mixed>, message?: string}
     */
    public function login(string $email, string $password): array
    {
        $response = $this->request()
            ->post('/auth/login', [
                'email' => $email,
                'password' => $password,
                'device_name' => 'babysteps-mobile',
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeToken($data['token']);

            return [
                'success' => true,
                'user' => $data['user'],
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message', __('Invalid credentials')),
        ];
    }

    /**
     * Register a new account on the backend and store the received token.
     *
     * @return array{success: bool, user?: array<string, mixed>, errors?: array<string, mixed>, message?: string}
     */
    public function register(string $name, string $username, string $email, string $password): array
    {
        $response = $this->request()
            ->post('/auth/register', [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'device_name' => 'babysteps-mobile',
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeToken($data['token']);

            return [
                'success' => true,
                'user' => $data['user'],
            ];
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
     * Validate the stored token against the backend.
     *
     * @return array{valid: bool, user?: array<string, mixed>}
     */
    public function validateToken(): array
    {
        $token = $this->getToken();

        if (! $token) {
            return ['valid' => false];
        }

        $response = $this->authenticatedRequest()
            ->get('/auth/me');

        if ($response->successful()) {
            return [
                'valid' => true,
                'user' => $response->json('user'),
            ];
        }

        $this->clearToken();

        return ['valid' => false];
    }

    /**
     * Log out by revoking the token on the backend and clearing local storage.
     */
    public function logout(): void
    {
        $token = $this->getToken();

        if ($token) {
            $this->authenticatedRequest()
                ->post('/auth/logout');
        }

        $this->clearToken();
    }

    /**
     * Make an authenticated GET request to the backend API.
     */
    public function get(string $path): Response
    {
        return $this->authenticatedRequest()->get($path);
    }

    /**
     * Make an authenticated POST request to the backend API.
     *
     * @param  array<string, mixed>  $data
     */
    public function post(string $path, array $data = []): Response
    {
        return $this->authenticatedRequest()->post($path, $data);
    }

    /**
     * Make an authenticated PUT request to the backend API.
     *
     * @param  array<string, mixed>  $data
     */
    public function put(string $path, array $data = []): Response
    {
        return $this->authenticatedRequest()->put($path, $data);
    }

    /**
     * Make an authenticated DELETE request to the backend API.
     */
    public function delete(string $path): Response
    {
        return $this->authenticatedRequest()->delete($path);
    }

    public function request(): PendingRequest
    {
        return Http::baseUrl(config('api-client.base_url'))
            ->timeout(config('api-client.timeout'))
            ->acceptJson();
    }

    public function authenticatedRequest(): PendingRequest
    {
        return $this->request()
            ->withToken($this->getToken());
    }
}
