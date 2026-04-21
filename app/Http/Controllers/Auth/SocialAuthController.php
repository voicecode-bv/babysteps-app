<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesAuthenticatedSession;
use App\Http\Controllers\Controller;
use App\Jobs\SyncDeviceInfo;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialAuthController extends Controller
{
    use HandlesAuthenticatedSession;

    public function __construct(protected ApiClient $apiClient) {}

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'email' => $this->errorMessage($request->string('error')->toString()),
            ]);
        }

        $token = $request->string('token')->toString();

        if ($token === '') {
            return redirect()->route('login')->withErrors([
                'email' => __('Social sign-in failed'),
            ]);
        }

        $this->apiClient->storeToken($token);

        $result = $this->apiClient->validateToken();

        if (! ($result['valid'] ?? false) || ! isset($result['user'])) {
            $this->apiClient->clearToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Social sign-in failed'),
            ]);
        }

        $this->syncLocalUser($result['user']);

        SyncDeviceInfo::dispatch();

        $this->primeSettingsCache($this->apiClient);

        if (Auth::user()?->notifications_prompted_at === null) {
            return redirect()->route('onboarding.intro');
        }

        return redirect()->route('feed');
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'missing_email' => __('Social sign-in failed: missing email'),
            default => __('Social sign-in failed'),
        };
    }
}
