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
    public function show(): Response
    {
        return Inertia::render('Map', [
            'mapboxToken' => config('services.mapbox.token'),
        ]);
    }

    public function photos(Request $request, ApiClient $apiClient): JsonResponse
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
            $response = $apiClient->get('/photos/map?'.$query);
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
