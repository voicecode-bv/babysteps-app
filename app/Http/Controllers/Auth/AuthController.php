<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesAuthenticatedSession;
use App\Http\Controllers\Controller;
use App\Jobs\SyncDeviceInfo;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Native\Mobile\Edge\Edge;

class AuthController extends Controller
{
    use HandlesAuthenticatedSession;

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

        $this->primeSettingsCache($this->apiClient);

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

        $this->primeSettingsCache($this->apiClient);

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

        $this->primeSettingsCache($this->apiClient);

        return redirect()->route('feed');
    }
}
