<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Settings/Persons', [
            'persons' => Inertia::defer(fn () => $this->fetchPersons()),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
        ]);

        try {
            $response = $this->apiClient->post('/tags', [
                'type' => 'person',
                'name' => $validated['name'],
                'birthdate' => $validated['birthdate'] ?? null,
            ]);
        } catch (ConnectionException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('Could not connect to the server')], 503);
            }

            return back()->withErrors(['name' => __('Could not connect to the server')]);
        }

        if ($response->failed()) {
            if ($request->expectsJson()) {
                return response()->json(
                    $response->json() ?? ['message' => __('Failed to add person')],
                    $response->status(),
                );
            }

            return back()->withErrors([
                'name' => $response->json('errors.name.0')
                    ?? $response->json('message', __('Failed to add person')),
                'birthdate' => $response->json('errors.birthdate.0'),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->apiClient->proxyMediaUrls($response->json('data')), 201);
        }

        return back();
    }

    public function update(Request $request, int $person): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
        ]);

        try {
            $response = $this->apiClient->put("/tags/{$person}", [
                'name' => $validated['name'],
                'birthdate' => $validated['birthdate'] ?? null,
            ]);
        } catch (ConnectionException) {
            return back()->withErrors(['name' => __('Could not connect to the server')]);
        }

        if ($response->failed()) {
            return back()->withErrors([
                'name' => $response->json('errors.name.0')
                    ?? $response->json('message', __('Failed to update person')),
                'birthdate' => $response->json('errors.birthdate.0'),
            ]);
        }

        return back();
    }

    public function destroy(int $person): RedirectResponse
    {
        try {
            $this->apiClient->delete("/tags/{$person}");
        } catch (ConnectionException) {
            return back()->withErrors(['person' => __('Could not connect to the server')]);
        }

        return back();
    }

    public function updatePhoto(Request $request, int $person): RedirectResponse
    {
        $validated = $request->validate([
            'photo_path' => ['required', 'string'],
        ]);

        $path = $validated['photo_path'];

        abort_unless(file_exists($path), 422, __('Image file not found.'));

        $mimeType = File::mimeType($path);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            default => pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg',
        };

        $filename = 'person-avatar.'.$extension;

        try {
            $response = $this->apiClient->authenticated()
                ->attach('avatar', file_get_contents($path), $filename, ['Content-Type' => $mimeType])
                ->post("/tags/{$person}/avatar");
        } catch (ConnectionException) {
            return back()->withErrors(['photo' => __('Could not connect to the server')]);
        }

        if ($response->failed()) {
            return back()->withErrors([
                'photo' => $response->json('errors.avatar.0')
                    ?? $response->json('message', __('Failed to upload photo')),
            ]);
        }

        return back();
    }

    public function deletePhoto(int $person): RedirectResponse
    {
        try {
            $this->apiClient->delete("/tags/{$person}/avatar");
        } catch (ConnectionException) {
            return back()->withErrors(['photo' => __('Could not connect to the server')]);
        }

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchPersons(): array
    {
        try {
            $response = $this->apiClient->get('/tags?type=person');
        } catch (ConnectionException) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return $this->apiClient->proxyMediaUrls($response->json('data')) ?? [];
    }
}
