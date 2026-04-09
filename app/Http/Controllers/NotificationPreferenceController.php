<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(ApiClient $apiClient): JsonResponse
    {
        $response = $apiClient->get('/notification-preferences');

        return response()->json($response->json('data'));
    }

    public function update(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'post_liked' => ['required', 'boolean'],
            'post_commented' => ['required', 'boolean'],
            'comment_liked' => ['required', 'boolean'],
            'new_circle_post' => ['required', 'boolean'],
            'circle_invitation_accepted' => ['required', 'boolean'],
        ]);

        $apiClient->put('/notification-preferences', $validated);

        return back();
    }
}
