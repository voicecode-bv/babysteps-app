<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    public function index(ApiClient $apiClient): Response
    {
        $response = $apiClient->get('/circles');

        return Inertia::render('Circles/Index', [
            'circles' => $response->json('data'),
        ]);
    }

    public function show(int $circle, ApiClient $apiClient): Response
    {
        $response = $apiClient->get("/circles/{$circle}");

        return Inertia::render('Circles/Show', [
            'circle' => $response->json('data'),
        ]);
    }
}
