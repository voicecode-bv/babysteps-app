<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DefaultCircleController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Settings/DefaultCircles', [
            'circles' => $this->apiClient->cachedCircles(),
            'defaultCircleIds' => Inertia::defer(fn () => $this->fetchDefaultCircleIds()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'circle_ids' => ['present', 'array'],
            'circle_ids.*' => ['integer'],
        ]);

        $this->apiClient->put('/default-circles', $validated);

        return back();
    }

    /**
     * @return array<int, int>
     */
    protected function fetchDefaultCircleIds(): array
    {
        try {
            $response = $this->apiClient->get('/default-circles');
        } catch (ConnectionException) {
            return [];
        }

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }
}
