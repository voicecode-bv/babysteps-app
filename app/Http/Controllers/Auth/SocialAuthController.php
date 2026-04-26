<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\HandlesAuthenticatedSession;
use App\Http\Controllers\Controller;
use App\Jobs\SyncDeviceInfo;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    use HandlesAuthenticatedSession;

    public function __construct(protected ApiClient $apiClient) {}

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect('/login?oauth_error='.urlencode($request->string('error')->toString()));
        }

        $token = $request->string('token')->toString();

        if ($token === '') {
            return redirect('/login?oauth_error=missing_token');
        }

        $this->apiClient->storeToken($token);

        $result = $this->apiClient->validateToken();

        if (! ($result['valid'] ?? false) || ! isset($result['user'])) {
            $this->apiClient->clearToken();

            return redirect('/login?oauth_error=invalid_token');
        }

        $this->syncLocalUser($result['user']);

        SyncDeviceInfo::dispatch();

        $this->primeSettingsCache($this->apiClient);

        return redirect('/?oauth=success');
    }
}
