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
        $serviceKeys = $apiClient->cachedServiceKeys();

        return Inertia::render('PostDetail', [
            'post' => $apiClient->proxyMediaUrls($response->json('data')),
            'mapboxToken' => $serviceKeys['mapbox']['public_token'] ?? null,
            'availableCircles' => fn () => $apiClient->get('/circles')->json('data') ?? [],
            'availableTags' => fn () => array_values(array_filter(
                $apiClient->get('/tags?type=tag')->json('data') ?? [],
                fn ($tag) => ($tag['type'] ?? 'tag') === 'tag',
            )),
            'availablePersons' => fn () => $apiClient->proxyMediaUrls(
                $apiClient->get('/tags?type=person')->json('data') ?? []
            ) ?? [],
        ]);
    }
}
