<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        return Inertia::render('Notifications', [
            'circleInvitations' => fn () => $apiClient->get('/circle-invitations')->json('data', []),
            'ownershipTransfers' => fn () => $apiClient->proxyMediaUrls($apiClient->get('/circle-ownership-transfers')->json('data', [])),
            'notifications' => function () use ($apiClient) {
                $response = $apiClient->get('/notifications');

                if ($response->failed()) {
                    return ['data' => [], 'meta' => $this->emptyMeta()];
                }

                return [
                    'data' => $apiClient->proxyMediaUrls($response->json('data', [])) ?? [],
                    'meta' => $response->json('meta', $this->emptyMeta()),
                ];
            },
        ]);
    }

    public function loadPage(Request $request, ApiClient $apiClient): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));

        try {
            $response = $apiClient->get("/notifications?page={$page}");
        } catch (ConnectionException) {
            return response()->json(['message' => __('Could not connect to the server.')], 503);
        }

        if ($response->failed()) {
            return response()->json(
                ['message' => $response->json('message', __('Failed to load notifications'))],
                $response->status(),
            );
        }

        return response()->json([
            'data' => $apiClient->proxyMediaUrls($response->json('data', [])) ?? [],
            'meta' => $response->json('meta', $this->emptyMeta()),
        ]);
    }

    public function markAsRead(ApiClient $apiClient): RedirectResponse
    {
        $apiClient->post('/notifications/read', request()->only('ids'));

        Cache::forget('unread_notification_count');

        return back();
    }

    /**
     * @return array{current_page: int, last_page: int, per_page: int, total: int}
     */
    private function emptyMeta(): array
    {
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 20,
            'total' => 0,
        ];
    }
}
