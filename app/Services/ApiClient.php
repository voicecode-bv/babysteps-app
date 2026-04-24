<?php

namespace App\Services;

use App\Services\TokenStore\TokenStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function __construct(protected TokenStore $tokenStore) {}

    public function getToken(): ?string
    {
        return $this->tokenStore->get();
    }

    public function storeToken(string $token): bool
    {
        return $this->tokenStore->set($token);
    }

    public function clearToken(): bool
    {
        return $this->tokenStore->delete();
    }

    public function hasToken(): bool
    {
        return $this->tokenStore->has();
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
                    'device_name' => 'innerr-mobile',
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
                    'device_name' => 'innerr-mobile',
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

    public const SETTINGS_CACHE_TTL = 300;

    public static function profileCacheKey(int $userId): string
    {
        return 'settings_profile_'.$userId;
    }

    public static function circlesCacheKey(): string
    {
        return 'circles';
    }

    public static function serviceKeysCacheKey(): string
    {
        return 'service_keys';
    }

    /**
     * Fetch and cache the profile for a given username. Returns null on failure.
     *
     * @return array<string, mixed>|null
     */
    public function cachedProfile(int $userId, string $username): ?array
    {
        return Cache::remember(
            self::profileCacheKey($userId),
            self::SETTINGS_CACHE_TTL,
            function () use ($username) {
                try {
                    $response = $this->get("/profiles/{$username}");
                } catch (ConnectionException) {
                    return null;
                }

                if ($response->failed() || $response->json('data') === null) {
                    return null;
                }

                return $this->proxyMediaUrls($response->json('data'));
            },
        );
    }

    /**
     * Fetch and cache the current user's circles.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cachedCircles(): array
    {
        return Cache::remember(
            self::circlesCacheKey(),
            self::SETTINGS_CACHE_TTL,
            function () {
                try {
                    $response = $this->get('/circles');
                } catch (ConnectionException) {
                    return [];
                }

                return $response->successful()
                    ? $this->proxyMediaUrls($response->json('data')) ?? []
                    : [];
            },
        );
    }

    /**
     * Fetch and cache public service keys (Mapbox, Flare, etc.) from the API.
     *
     * @return array<string, array<string, string|null>>
     */
    public function cachedServiceKeys(): array
    {
        $cached = Cache::get(self::serviceKeysCacheKey());

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = $this->get('/service-keys');
        } catch (ConnectionException) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json() ?? [];

        if ($data !== []) {
            Cache::put(self::serviceKeysCacheKey(), $data, self::SETTINGS_CACHE_TTL);
        }

        return $data;
    }

    /**
     * Rewrite signed API media URLs to go through the local caching proxy.
     *
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    public function proxyMediaUrls(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $json = json_encode($data);

        $apiHost = parse_url(config('api-client.base_url'), PHP_URL_HOST);

        $json = preg_replace_callback(
            '#https?://'.preg_quote($apiHost, '#').'/api/media/[^"\\\\]+#',
            fn ($matches) => url('/media-proxy?url='.urlencode($matches[0])),
            $json,
        );

        return json_decode($json, true);
    }
}
