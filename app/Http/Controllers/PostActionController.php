<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostActionController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,mp4,mov', 'max:51200'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'location' => ['nullable', 'string', 'max:255'],
            'circle_ids' => ['nullable', 'array'],
            'circle_ids.*' => ['integer'],
        ]);

        $data = [
            'caption' => $validated['caption'] ?? '',
            'location' => $validated['location'] ?? '',
        ];

        if (! empty($validated['circle_ids'])) {
            $data['circle_ids'] = $validated['circle_ids'];
        }

        $response = $this->apiClient->authenticated()
            ->attach('media', file_get_contents($validated['media']->getRealPath()), $validated['media']->getClientOriginalName())
            ->post('/posts', $data);

        if ($response->successful()) {
            return redirect()->route('feed');
        }

        return back()->withErrors(['media' => $response->json('message', __('Failed to create post'))]);
    }

    public function destroy(int $post): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$post}");

        return redirect()->route('feed');
    }

    public function like(int $post): RedirectResponse
    {
        $this->apiClient->post("/posts/{$post}/like");

        return back();
    }

    public function unlike(int $post): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$post}/like");

        return back();
    }

    public function comment(Request $request, int $post): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $this->apiClient->post("/posts/{$post}/comments", $validated);

        return back();
    }

    public function destroyComment(int $comment): RedirectResponse
    {
        $this->apiClient->delete("/comments/{$comment}");

        return back();
    }

    public function likeComment(int $comment): RedirectResponse
    {
        $this->apiClient->post("/comments/{$comment}/like");

        return back();
    }

    public function unlikeComment(int $comment): RedirectResponse
    {
        $this->apiClient->delete("/comments/{$comment}/like");

        return back();
    }
}
