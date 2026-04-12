<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DefaultCircleController extends Controller
{
    public function index(ApiClient $apiClient): JsonResponse
    {
        $response = $apiClient->get('/default-circles');

        return response()->json($response->json('data'));
    }

    public function update(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'circle_ids' => ['present', 'array'],
            'circle_ids.*' => ['integer'],
        ]);

        $apiClient->put('/default-circles', $validated);

        return back();
    }
}
