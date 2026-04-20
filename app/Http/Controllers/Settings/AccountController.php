<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Native\Mobile\Edge\Edge;

class AccountController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Settings/Account');
    }

    public function destroy(): RedirectResponse
    {
        $userId = Auth::id();

        $response = $this->apiClient->delete('/account');

        if ($response->failed()) {
            return back()->withErrors([
                'account' => __('We could not delete your account. Please try again later.'),
            ]);
        }

        $this->apiClient->clearToken();

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
}
