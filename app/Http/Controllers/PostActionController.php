<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Server-side helpers voor media uit NativePhp Camera-paden:
 *   - serveMedia        leest file:// pad → data-url voor preview
 *   - storeCroppedMedia base64 cropped beeld → tijdelijk bestand op disk
 *
 * De daadwerkelijke post-create gaat via Spa\PostsController::store.
 */
class PostActionController extends Controller
{
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

        $directory = self::croppedMediaDirectory();
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.uniqid('crop_', true).'.jpg';
        file_put_contents($path, $contents);

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

    public static function croppedMediaDirectory(): string
    {
        return storage_path('app/private/cropped');
    }
}
