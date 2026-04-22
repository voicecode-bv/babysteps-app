<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function show(int $post, ApiClient $apiClient): Response
    {
        $response = $apiClient->get("/posts/{$post}");

        return Inertia::render('PostDetail', [
            'post' => $apiClient->proxyMediaUrls($response->json('data')),
            'availableCircles' => fn () => $apiClient->get('/circles')->json('data') ?? [],
        ]);
    }
}
