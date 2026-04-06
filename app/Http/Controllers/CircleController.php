<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        return Inertia::render('Circles/Index', [
            'circles' => Inertia::defer(fn () => $apiClient->proxyMediaUrls($apiClient->get('/circles')->json('data'))),
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
