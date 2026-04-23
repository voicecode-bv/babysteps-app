<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        // The cropped flow drops a sidecar with EXIF the client read off the
        // original (browser canvas re-encode strips EXIF before the bytes get
        // here). Forward it as multipart form fields alongside the file.
        $sidecarPath = $path.'.exif.json';

        if (file_exists($sidecarPath)) {
            $exif = json_decode((string) file_get_contents($sidecarPath), true) ?? [];

            foreach (['taken_at', 'latitude', 'longitude'] as $key) {
                if (isset($exif[$key]) && $exif[$key] !== null) {
                    $data[$key] = $exif[$key];
                }
            }
        }

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

        if (str_starts_with($path, $this->croppedMediaDirectory())) {
            @unlink($path);
            @unlink($sidecarPath);
        }

        if ($response->successful()) {
            return redirect()->route('feed');
        }

        $apiErrors = $response->json('errors');

        if (is_array($apiErrors) && $apiErrors !== []) {
            $flattened = [];

            foreach ($apiErrors as $field => $messages) {
                $key = $field === 'media' ? 'media_path' : $field;
                $flattened[$key] = is_array($messages) ? (string) ($messages[0] ?? '') : (string) $messages;
            }

            return back()->withErrors($flattened);
        }

        return back()->withErrors(['media_path' => $response->json('message', __('Failed to create post'))]);
    }

    public function storeCroppedMedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'string'],
            'taken_at' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $contents = base64_decode($validated['data'], true);

        if ($contents === false) {
            return response()->json(['message' => __('Invalid cropped image')], 422);
        }

        $size = strlen($contents);

        if ($size === 0 || $size > 20 * 1024 * 1024) {
            return response()->json(['message' => __('Invalid cropped image')], 422);
        }

        $directory = $this->croppedMediaDirectory();
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.uniqid('crop_', true).'.jpg';
        file_put_contents($path, $contents);

        // Browser cropping re-encodes the JPEG and strips EXIF, so the client
        // reads it from the original first and forwards it here. Persist as a
        // sidecar that the upload step will pick up alongside the JPEG.
        $exif = array_filter([
            'taken_at' => $validated['taken_at'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ], fn ($value) => $value !== null);

        if ($exif !== []) {
            file_put_contents($path.'.exif.json', json_encode($exif));
        }

        return response()->json(['path' => $path]);
    }

    protected function croppedMediaDirectory(): string
    {
        return storage_path('app/private/cropped');
    }

    public function update(Request $request, int $post): RedirectResponse
    {
        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:2200'],
            'circle_ids' => ['required', 'array', 'min:1'],
            'circle_ids.*' => ['integer'],
        ]);

        try {
            $response = $this->apiClient->put("/posts/{$post}", [
                'caption' => $validated['caption'] ?? '',
                'circle_ids' => $validated['circle_ids'],
            ]);
        } catch (ConnectionException) {
            return back()->withErrors(['caption' => __('Could not connect to the server.')]);
        }

        if ($response->failed()) {
            return back()->withErrors(['caption' => $response->json('message', __('Failed to update post'))]);
        }

        return redirect()->route('posts.show', $post);
    }

    public function destroy(int $post): RedirectResponse
    {
        $this->apiClient->delete("/posts/{$post}");

        return redirect()->route('feed');
    }

    public function like(int $post): Response
    {
        try {
            $response = $this->apiClient->post("/posts/{$post}/like");
        } catch (ConnectionException) {
            return response('', 503);
        }

        if ($response->failed()) {
            return response('', $response->status());
        }

        return response()->noContent();
    }

    public function unlike(int $post): Response
    {
        try {
            $response = $this->apiClient->delete("/posts/{$post}/like");
        } catch (ConnectionException) {
            return response('', 503);
        }

        if ($response->failed()) {
            return response('', $response->status());
        }

        return response()->noContent();
    }

    public function indexLikes(int $post): JsonResponse
    {
        try {
            $response = $this->apiClient->get("/posts/{$post}/likes");
        } catch (ConnectionException) {
            return response()->json(['message' => __('Could not connect to the server.')], 503);
        }

        if ($response->failed()) {
            return response()->json(['message' => $response->json('message', __('Failed to load likes'))], $response->status());
        }

        return response()->json($this->apiClient->proxyMediaUrls($response->json()) ?? []);
    }

    public function indexComments(int $post): JsonResponse
    {
        try {
            $response = $this->apiClient->get("/posts/{$post}");
        } catch (ConnectionException) {
            return response()->json(['message' => __('Could not connect to the server.')], 503);
        }

        if ($response->failed()) {
            return response()->json(['message' => $response->json('message', __('Failed to load comments'))], $response->status());
        }

        $data = $this->apiClient->proxyMediaUrls($response->json('data')) ?? [];

        return response()->json($data['comments'] ?? []);
    }

    public function comment(Request $request, int $post): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_comment_id' => ['nullable', 'integer'],
        ]);

        try {
            $response = $this->apiClient->post("/posts/{$post}/comments", $validated);
        } catch (ConnectionException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('Could not connect to the server.')], 503);
            }

            return back()->withErrors(['comment' => __('Could not connect to the server.')]);
        }

        if ($request->expectsJson()) {
            if ($response->failed()) {
                return response()->json(['message' => $response->json('message', __('Failed to post comment'))], $response->status());
            }

            return response()->json($this->apiClient->proxyMediaUrls($response->json('data')));
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

    public function likeComment(int $comment): Response
    {
        try {
            $response = $this->apiClient->post("/comments/{$comment}/like");
        } catch (ConnectionException) {
            return response('', 503);
        }

        if ($response->failed()) {
            return response('', $response->status());
        }

        return response()->noContent();
    }

    public function unlikeComment(int $comment): Response
    {
        try {
            $response = $this->apiClient->delete("/comments/{$comment}/like");
        } catch (ConnectionException) {
            return response('', 503);
        }

        if ($response->failed()) {
            return response('', $response->status());
        }

        return response()->noContent();
    }
}
