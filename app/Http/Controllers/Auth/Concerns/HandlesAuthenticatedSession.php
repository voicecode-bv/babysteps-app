<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait HandlesAuthenticatedSession
{
    /**
     * @param  array<string, mixed>  $userData
     */
    protected function syncLocalUser(array $userData): void
    {
        User::query()
            ->where('api_user_id', '!=', $userData['id'])
            ->where(function ($query) use ($userData): void {
                $query->where('email', $userData['email'])
                    ->orWhere('username', $userData['username']);
            })
            ->delete();

        $user = User::updateOrCreate(
            ['api_user_id' => $userData['id']],
            [
                'email' => $userData['email'],
                'name' => $userData['name'],
                'username' => $userData['username'],
                'avatar' => $userData['avatar'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'locale' => $userData['locale'] ?? config('app.locale'),
                'password' => 'api-managed',
            ],
        );

        Auth::login($user);

        session()->regenerate();
    }

    protected function primeSettingsCache(ApiClient $apiClient): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        try {
            Cache::forget(ApiClient::profileCacheKey($user->id));
            Cache::forget(ApiClient::circlesCacheKey());

            $apiClient->cachedProfile($user->id, $user->username);
            $apiClient->cachedCircles();
        } catch (\Throwable $e) {
            Log::warning('Failed to prime settings cache after auth', ['error' => $e->getMessage()]);
        }
    }
}
