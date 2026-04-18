<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncDeviceInfo;
use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Native\Mobile\Edge\Edge;

class AuthController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->apiClient->login($validated['email'], $validated['password']);

        if (! $result['success']) {
            return back()->withErrors(['email' => $result['message']]);
        }

        $this->syncLocalUser($result['user']);

        SyncDeviceInfo::dispatch();

        $this->primeSettingsCache();

        if (Auth::user()?->notifications_prompted_at === null) {
            return redirect()->route('onboarding.notifications');
        }

        return redirect()->route('feed');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'terms_accepted' => ['accepted'],
        ]);

        $result = $this->apiClient->register(
            $validated['name'],
            $validated['username'],
            $validated['email'],
            $validated['password'],
        );

        if (! $result['success']) {
            return back()
                ->withErrors($result['errors'] ?? ['email' => $result['message']])
                ->withInput($request->except('password'));
        }

        $this->syncLocalUser($result['user']);

        SyncDeviceInfo::dispatch();

        $this->primeSettingsCache();

        return redirect()->route('onboarding.notifications');
    }

    public function logout(): RedirectResponse
    {
        $userId = Auth::id();

        $this->apiClient->logout();

        Auth::logout();

        if ($userId !== null) {
            Cache::forget(ApiClient::profileCacheKey($userId));
        }
        Cache::forget(ApiClient::circlesCacheKey());
        Cache::forget('unread_notification_count');

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Clear bottom bar to make it disappear.
        Edge::clear();

        return redirect()->route('login');
    }

    public function verify(): RedirectResponse
    {
        $result = $this->apiClient->validateToken();

        if (! $result['valid']) {
            Auth::logout();

            return redirect()->route('login');
        }

        $this->syncLocalUser($result['user']);

        SyncDeviceInfo::dispatch();

        $this->primeSettingsCache();

        return redirect()->route('feed');
    }

    /**
     * Prime the settings cache so the Settings page can render from cache on first visit.
     */
    protected function primeSettingsCache(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        try {
            Cache::forget(ApiClient::profileCacheKey($user->id));
            Cache::forget(ApiClient::circlesCacheKey());

            $this->apiClient->cachedProfile($user->id, $user->username);
            $this->apiClient->cachedCircles();
        } catch (\Throwable $e) {
            Log::warning('Failed to prime settings cache after auth', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    protected function syncLocalUser(array $userData): void
    {
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'api_user_id' => $userData['id'],
                'name' => $userData['name'],
                'username' => $userData['username'],
                'avatar' => $userData['avatar'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'locale' => $userData['locale'] ?? config('app.locale'),
                'password' => 'api-managed',
            ],
        );

        Auth::login($user);
    }
}
