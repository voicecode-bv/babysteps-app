<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        return Inertia::render('Notifications', [
            'circleInvitations' => fn () => $apiClient->get('/circle-invitations')->json('data', []),
            'notifications' => Inertia::scroll(function () use ($apiClient) {
                $page = request()->integer('page', 1);
                $response = $apiClient->get('/notifications?page='.$page);

                if ($response->failed()) {
                    return new LengthAwarePaginator([], 0, 15);
                }

                $data = $response->json();

                return new LengthAwarePaginator(
                    items: $apiClient->proxyMediaUrls($data['data']),
                    total: $data['meta']['total'],
                    perPage: $data['meta']['per_page'],
                    currentPage: $data['meta']['current_page'],
                );
            }),
        ]);
    }

    public function markAsRead(ApiClient $apiClient): RedirectResponse
    {
        $apiClient->post('/notifications/read', request()->only('ids'));

        Cache::forget('unread_notification_count');

        return back();
    }
}
