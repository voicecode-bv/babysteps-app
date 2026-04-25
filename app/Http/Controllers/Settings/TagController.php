<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Settings/Tags', [
            'tags' => Inertia::defer(fn () => $this->fetchTags()),
        ]);
    }

    public function update(Request $request, int $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        try {
            $response = $this->apiClient->put("/tags/{$tag}", $validated);
        } catch (ConnectionException) {
            return back()->withErrors(['name' => __('Could not connect to the server')]);
        }

        if ($response->failed()) {
            return back()->withErrors([
                'name' => $response->json('errors.name.0')
                    ?? $response->json('message', __('Failed to update tag')),
            ]);
        }

        return back();
    }

    public function destroy(int $tag): RedirectResponse
    {
        try {
            $this->apiClient->delete("/tags/{$tag}");
        } catch (ConnectionException) {
            return back()->withErrors(['tag' => __('Could not connect to the server')]);
        }

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchTags(): array
    {
        try {
            $response = $this->apiClient->get('/tags');
        } catch (ConnectionException) {
            return [];
        }

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }
}
