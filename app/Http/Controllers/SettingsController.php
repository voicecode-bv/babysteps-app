<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(ApiClient $apiClient): Response
    {
        $username = auth()->user()->username;
        $profileResponse = $apiClient->get("/profiles/{$username}");

        if ($profileResponse->failed() || $profileResponse->json('data') === null) {
            abort(404);
        }

        $profile = $apiClient->proxyMediaUrls($profileResponse->json('data'));

        return Inertia::render('Settings', [
            'profile' => $profile,
        ]);
    }
}
