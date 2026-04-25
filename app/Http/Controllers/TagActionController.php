<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagActionController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function index(): JsonResponse
    {
        try {
            $response = $this->apiClient->get('/tags');
        } catch (ConnectionException) {
            return response()->json(['message' => __('Could not connect to the server.')], 503);
        }

        if ($response->failed()) {
            return response()->json(['message' => $response->json('message', __('Failed to load tags'))], $response->status());
        }

        return response()->json($response->json('data') ?? []);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        try {
            $response = $this->apiClient->post('/tags', $validated);
        } catch (ConnectionException) {
            return response()->json(['message' => __('Could not connect to the server.')], 503);
        }

        if ($response->failed()) {
            return response()->json(
                $response->json() ?? ['message' => __('Failed to create tag')],
                $response->status(),
            );
        }

        return response()->json($response->json('data'), 201);
    }
}
