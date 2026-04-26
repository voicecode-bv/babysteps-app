<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $apiBase = rtrim((string) config('api-client.base_url'), '/');

        // Sync lokaal user-mirror met externe API (avatar/bio/locale/onboarded_at
        // kunnen via directe SPA→API calls zijn gewijzigd zonder dat de BFF het ziet).
        if ($user && $this->apiClient->hasToken()) {
            $result = $this->apiClient->validateToken();
            if (($result['valid'] ?? false) && isset($result['user'])) {
                $user->forceFill([
                    'avatar' => $result['user']['avatar'] ?? $user->avatar,
                    'bio' => $result['user']['bio'] ?? $user->bio,
                    'locale' => $result['user']['locale'] ?? $user->locale,
                    'onboarded_at' => $result['user']['onboarded_at'] ?? $user->onboarded_at,
                ])->save();
                $user->refresh();
            }
        }

        return response()->json([
            'user' => $user ? [
                'id' => (int) $user->api_user_id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'locale' => $user->locale,
                'onboarded' => $user->onboarded_at !== null,
            ] : null,
            'token' => $user ? $this->apiClient->getToken() : null,
            'locale' => app()->getLocale(),
            'api_base' => $apiBase,
            'social_auth_urls' => [
                'google' => $apiBase.'/oauth/google/redirect',
                'apple' => $apiBase.'/oauth/apple/redirect',
            ],
        ]);
    }
}
