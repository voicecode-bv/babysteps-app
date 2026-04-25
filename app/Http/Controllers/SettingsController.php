<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(ApiClient $apiClient): Response
    {
        $user = auth()->user();

        $profile = $apiClient->cachedProfile($user->id, $user->username);

        if ($profile === null) {
            abort(503);
        }

        return Inertia::render('Settings', [
            'profile' => $profile,
        ]);
    }
}
