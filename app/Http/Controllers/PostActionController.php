<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PostActionController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function serveMedia(Request $request): JsonResponse
    {
        $path = $request->query('path');

        abort_unless($path && file_exists($path), 404);

        $mime = File::mimeType($path) ?: 'application/octet-stream';
        $base64 = base64_encode(File::get($path));

        return response()->json([
            'data_url' => "data:{$mime};base64,{$base64}",
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media_path' => ['required', 'string'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'location' => ['nullable', 'string', 'max:255'],
            'circle_ids' => ['required', 'array'],
            'circle_ids.*' => ['integer'],
        ]);

        $path = $validated['media_path'];

        abort_unless(file_exists($path), 422, __('Media file not found.'));

        $data = [
            'caption' => $validated['caption'] ?? '',
            'location' => $validated['location'] ?? '',
            'circle_ids' => $validated['circle_ids'],
        ];

        $mimeType = File::mimeType($path);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            default => pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg',
        };

        $filename = pathinfo($path, PATHINFO_FILENAME).'.'.$extension;

        $response = $this->apiClient->authenticated()
            ->attach('media', file_get_contents($path), $filename, ['Content-Type' => $mimeType])
            ->post('/posts', $data);

        if ($response->successful()) {
            return redirect()->route('feed');
        }

        return back()->withErrors(['media_path' => $response->json('message', __('Failed to create post'))]);
    }

    public function destroy(int $post): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$post}");

        return redirect()->route('feed');
    }

    public function like(int $post)
    {
        try {
            $this->apiClient->post("/posts/{$post}/like");
        } catch (ConnectionException) {
            return back()->withErrors(['like' => __('Could not connect to the server.')]);
        }
    }

    public function unlike(int $post)
    {
        try {
            $this->apiClient->delete("/posts/{$post}/like");
        } catch (ConnectionException) {
            return back()->withErrors(['like' => __('Could not connect to the server.')]);
        }
    }

    public function comment(Request $request, int $post): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_comment_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->apiClient->post("/posts/{$post}/comments", $validated);
        } catch (ConnectionException) {
            return back()->withErrors(['comment' => __('Could not connect to the server.')]);
        }

        return back();
    }

    public function destroyComment(int $comment): RedirectResponse
    {
        try {
            $this->apiClient->delete("/comments/{$comment}");
        } catch (ConnectionException) {
            return back()->withErrors(['comment' => __('Could not connect to the server.')]);
        }

        return back();
    }

    public function likeComment(int $comment)
    {
        try {
            $this->apiClient->post("/comments/{$comment}/like");
        } catch (ConnectionException) {
            return back()->withErrors(['like' => __('Could not connect to the server.')]);
        }
    }

    public function unlikeComment(int $comment)
    {
        try {
            $this->apiClient->delete("/comments/{$comment}/like");
        } catch (ConnectionException) {
            return back()->withErrors(['like' => __('Could not connect to the server.')]);
        }
    }
}
