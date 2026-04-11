<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(string $username, ApiClient $apiClient): Response
    {
        $page = request()->integer('page', 1);

        $profileResponse = $apiClient->get("/profiles/{$username}");

        if ($profileResponse->failed() || $profileResponse->json('data') === null) {
            abort(404);
        }

        $profile = $apiClient->proxyMediaUrls($profileResponse->json('data'));
        $postsResponse = $apiClient->get("/profiles/{$username}/posts?page={$page}")->json();

        $paginator = new LengthAwarePaginator(
            items: $apiClient->proxyMediaUrls($postsResponse['data']),
            total: $postsResponse['meta']['total'],
            perPage: $postsResponse['meta']['per_page'],
            currentPage: $postsResponse['meta']['current_page'],
        );

        return Inertia::render('Profile', [
            'profile' => $profile,
            'posts' => Inertia::scroll($paginator),
        ]);
    }

    public function updateBio(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:150'],
        ]);

        $apiClient->put('/profile', $validated);
        Cache::forget('settings_profile_'.auth()->id());

        return back();
    }

    public function updateAvatar(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'avatar_path' => ['required', 'string'],
        ]);

        $path = $validated['avatar_path'];

        abort_unless(file_exists($path), 422, __('Image file not found.'));

        $mimeType = File::mimeType($path);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            default => pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg',
        };

        $filename = 'avatar.'.$extension;

        $response = $apiClient->authenticated()
            ->attach('avatar', file_get_contents($path), $filename, ['Content-Type' => $mimeType])
            ->post('/profile/avatar');

        if ($response->successful()) {
            $avatarUrl = $response->json('user.avatar');
            $request->user()->update(['avatar' => $avatarUrl]);
            Cache::forget('settings_profile_'.auth()->id());
        }

        return back();
    }

    public function deleteAvatar(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $apiClient->delete('/profile/avatar');
        $request->user()->update(['avatar' => null]);
        Cache::forget('settings_profile_'.auth()->id());

        return back();
    }

    public function updateLocale(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,nl'],
        ]);

        $apiClient->put('/profile', $validated);

        $request->user()->update($validated);
        app()->setLocale($validated['locale']);
        Cache::forget('settings_profile_'.auth()->id());

        return to_route('settings');
    }
}
