<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(ApiClient $apiClient): Response
    {
        $username = auth()->user()->username;

        $profile = Cache::get('settings_profile');

        if ($profile === null) {
            try {
                $profileResponse = $apiClient->get("/profiles/{$username}");

                if ($profileResponse->failed() || $profileResponse->json('data') === null) {
                    abort(404);
                }

                $profile = $apiClient->proxyMediaUrls($profileResponse->json('data'));
                Cache::put('settings_profile', $profile, 300);
            } catch (ConnectionException) {
                abort(503);
            }
        }

        return Inertia::render('Settings', [
            'profile' => $profile,
        ]);
    }
}
