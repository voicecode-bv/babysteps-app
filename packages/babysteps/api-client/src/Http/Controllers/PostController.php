<?php

namespace Babysteps\ApiClient\Http\Controllers;

use Babysteps\ApiClient\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,mp4,mov', 'max:51200'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $response = $this->apiClient->authenticatedRequest()
            ->attach('media', file_get_contents($validated['media']->getRealPath()), $validated['media']->getClientOriginalName())
            ->post('/posts', [
                'caption' => $validated['caption'] ?? '',
                'location' => $validated['location'] ?? '',
            ]);

        if ($response->successful()) {
            return redirect()->route('feed');
        }

        return back()->withErrors(['media' => $response->json('message', __('Failed to create post'))]);
    }

    public function destroy(int $postId): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$postId}");

        return redirect()->route('feed');
    }

    public function like(int $postId): RedirectResponse
    {
        $this->apiClient->post("/posts/{$postId}/like");

        return back();
    }

    public function unlike(int $postId): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$postId}/like");

        return back();
    }

    public function comment(Request $request, int $postId): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $this->apiClient->post("/posts/{$postId}/comments", $validated);

        return back();
    }

    public function destroyComment(int $commentId): RedirectResponse
    {
        $this->apiClient->delete("/comments/{$commentId}");

        return back();
    }
}
