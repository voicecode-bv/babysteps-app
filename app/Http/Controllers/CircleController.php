<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        return Inertia::render('Circles/Index', [
            'circles' => Inertia::defer(function () use ($apiClient) {
                $cached = Cache::get('circles');

                if ($cached !== null) {
                    return $cached;
                }

                try {
                    $response = $apiClient->get('/circles');
                    $circles = $response->successful()
                        ? $apiClient->proxyMediaUrls($response->json('data'))
                        : [];
                } catch (ConnectionException) {
                    return [];
                }

                Cache::put('circles', $circles, 300);

                return $circles;
            }),
        ]);
    }

    public function show(int $circle, ApiClient $apiClient): Response
    {
        $response = $apiClient->get("/circles/{$circle}");
        $data = $apiClient->proxyMediaUrls($response->json('data'));

        return Inertia::render('Circles/Show', [
            'circle' => $data,
            'invitations' => $data['pending_invitations'] ?? [],
        ]);
    }
}
