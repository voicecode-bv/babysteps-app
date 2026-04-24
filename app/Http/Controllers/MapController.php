<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function show(ApiClient $apiClient): Response
    {
        $serviceKeys = $apiClient->cachedServiceKeys();

        return Inertia::render('Map', [
            'mapboxToken' => $serviceKeys['mapbox']['public_token'] ?? null,
        ]);
    }

    public function photos(Request $request, ApiClient $apiClient): JsonResponse
    {
        return $this->proxyPhotoMap($request, $apiClient, '/photos/map');
    }

    public function profile(string $username, ApiClient $apiClient): Response
    {
        $profileResponse = $apiClient->get("/profiles/{$username}");

        if ($profileResponse->failed() || $profileResponse->json('data') === null) {
            abort(404);
        }

        $serviceKeys = $apiClient->cachedServiceKeys();

        return Inertia::render('ProfileMap', [
            'mapboxToken' => $serviceKeys['mapbox']['public_token'] ?? null,
            'profile' => $apiClient->proxyMediaUrls($profileResponse->json('data')),
        ]);
    }

    public function profilePhotos(string $username, Request $request, ApiClient $apiClient): JsonResponse
    {
        return $this->proxyPhotoMap($request, $apiClient, "/profiles/{$username}/photos/map");
    }

    public function circle(int $circle, ApiClient $apiClient): Response
    {
        $circleResponse = $apiClient->get("/circles/{$circle}");

        if ($circleResponse->failed() || $circleResponse->json('data') === null) {
            abort($circleResponse->status() === 403 ? 403 : 404);
        }

        $serviceKeys = $apiClient->cachedServiceKeys();

        return Inertia::render('CircleMap', [
            'mapboxToken' => $serviceKeys['mapbox']['public_token'] ?? null,
            'circle' => $apiClient->proxyMediaUrls($circleResponse->json('data')),
        ]);
    }

    public function circlePhotos(int $circle, Request $request, ApiClient $apiClient): JsonResponse
    {
        return $this->proxyPhotoMap($request, $apiClient, "/circles/{$circle}/photos/map");
    }

    private function proxyPhotoMap(Request $request, ApiClient $apiClient, string $path): JsonResponse
    {
        $validated = $request->validate([
            'bbox' => ['required', 'string'],
            'media_type' => ['nullable', 'string', 'in:image,video,all'],
        ]);

        $query = http_build_query(array_filter([
            'bbox' => $validated['bbox'],
            'media_type' => $validated['media_type'] ?? null,
        ]));

        try {
            $response = $apiClient->get($path.'?'.$query);
        } catch (ConnectionException) {
            return response()->json(['type' => 'FeatureCollection', 'features' => [], 'truncated' => false], 200);
        }

        if ($response->failed()) {
            return response()->json(
                ['type' => 'FeatureCollection', 'features' => [], 'truncated' => false],
                $response->status(),
            );
        }

        return response()->json($apiClient->proxyMediaUrls($response->json()));
    }
}
