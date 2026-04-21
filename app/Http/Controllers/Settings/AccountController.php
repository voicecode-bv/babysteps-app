<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function export(): RedirectResponse
    {
        try {
            $response = $this->apiClient->post('/account/export');
        } catch (ConnectionException $e) {
            Log::warning('Account export failed: connection error', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'export' => __('Could not connect to the server'),
            ]);
        }

        if ($response->failed()) {
            Log::warning('Account export failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return back()->withErrors([
                'export' => __('We could not request your data export. Please try again later.'),
            ]);
        }

        return back();
    }

    public function destroy(): RedirectResponse
    {
        $user = Auth::user();
        $userId = $user?->id;

        try {
            $response = $this->apiClient->delete('/account');
        } catch (ConnectionException $e) {
            Log::warning('Account deletion failed: connection error', ['error' => $e->getMessage()]);

            return back()->withErrors([
                'account' => __('Could not connect to the server'),
            ]);
        }

        if ($response->failed()) {
            Log::warning('Account deletion failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return back()->withErrors([
                'account' => __('We could not delete your account. Please try again later.'),
            ]);
        }

        $this->apiClient->clearToken();

        Auth::logout();

        $user?->delete();

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
