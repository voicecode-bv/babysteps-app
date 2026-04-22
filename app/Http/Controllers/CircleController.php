<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    public function index(Request $request, ApiClient $apiClient): Response
    {
        if ($request->boolean('refresh')) {
            Cache::forget(ApiClient::circlesCacheKey());
        }

        return Inertia::render('Circles/Index', [
            'circles' => Inertia::defer(fn () => $apiClient->cachedCircles()),
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

    public function transferOwnership(int $circle, ApiClient $apiClient): Response
    {
        $response = $apiClient->get("/circles/{$circle}");
        $data = $apiClient->proxyMediaUrls($response->json('data'));

        $transfer = $data['pending_ownership_transfer'] ?? null;

        return Inertia::render('Circles/TransferOwnership', [
            'circle' => $data,
            'pendingTransfer' => $transfer,
        ]);
    }
}
