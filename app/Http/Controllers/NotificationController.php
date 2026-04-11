<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        return Inertia::render('Notifications', [
            'circleInvitations' => fn () => $apiClient->get('/circle-invitations')->json('data', []),
            'notifications' => function () use ($apiClient) {
                $response = $apiClient->get('/notifications');

                if ($response->failed()) {
                    return [];
                }

                return $apiClient->proxyMediaUrls($response->json('data', []));
            },
        ]);
    }

    public function markAsRead(ApiClient $apiClient): RedirectResponse
    {
        $apiClient->post('/notifications/read', request()->only('ids'));

        Cache::forget('unread_notification_count');

        return back();
    }
}
