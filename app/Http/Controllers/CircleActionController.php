<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CircleActionController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $response = $this->apiClient->post('/circles', $validated);

        if ($response->successful()) {
            Cache::forget(ApiClient::circlesCacheKey());
            $circleId = $response->json('data.id');

            return redirect()->route('circles.show', $circleId);
        }

        return back()->withErrors(['name' => $response->json('message', __('Failed to create circle'))]);
    }

    public function update(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $response = $this->apiClient->put("/circles/{$circle}", $validated);

        if ($response->successful()) {
            Cache::forget(ApiClient::circlesCacheKey());

            return back();
        }

        return back()->withErrors(['name' => $response->json('message', __('Failed to update circle'))]);
    }

    public function updateSettings(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'members_can_invite' => ['required', 'boolean'],
        ]);

        $response = $this->apiClient->put("/circles/{$circle}/settings", $validated);

        if ($response->successful()) {
            Cache::forget(ApiClient::circlesCacheKey());

            return back();
        }

        return back()->withErrors(['members_can_invite' => $response->json('message', __('Failed to update settings'))]);
    }

    public function destroy(int $circle): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}");
        Cache::forget(ApiClient::circlesCacheKey());

        return redirect()->route('circles.index');
    }

    public function addMember(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $data = str_contains($validated['identifier'], '@')
            ? ['email' => $validated['identifier']]
            : ['username' => $validated['identifier']];

        $response = $this->apiClient->post("/circles/{$circle}/members", $data);

        if ($response->successful()) {
            Cache::forget(ApiClient::circlesCacheKey());

            return back();
        }

        if ($response->status() === 429) {
            return back()->withErrors(['identifier' => __('Too many invitations sent. Please try again later.')]);
        }

        return back()->withErrors(['identifier' => $response->json('message', __('Failed to invite member'))]);
    }

    public function updatePhoto(Request $request, int $circle): RedirectResponse
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

        $filename = 'circle-photo.'.$extension;

        $this->apiClient->authenticated()
            ->attach('photo', file_get_contents($path), $filename, ['Content-Type' => $mimeType])
            ->post("/circles/{$circle}/photo");

        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function deletePhoto(int $circle): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/photo");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function acceptInvitation(int $invitation): RedirectResponse
    {
        $this->apiClient->post("/circle-invitations/{$invitation}/accept");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function declineInvitation(int $invitation): RedirectResponse
    {
        $this->apiClient->post("/circle-invitations/{$invitation}/decline");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function cancelInvitation(int $circle, int $invitation): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/invitations/{$invitation}");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function removeMember(int $circle, int $user): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/members/{$user}");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function leave(int $circle): RedirectResponse
    {
        $response = $this->apiClient->post("/circles/{$circle}/leave");

        if ($response->failed()) {
            return back()->withErrors(['leave' => $response->json('message', __('Failed to leave circle'))]);
        }

        Cache::forget(ApiClient::circlesCacheKey());

        return redirect()->route('circles.index');
    }

    public function initiateOwnershipTransfer(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $response = $this->apiClient->post("/circles/{$circle}/ownership-transfer", $validated);

        if ($response->failed()) {
            return back()->withErrors(['user_id' => $response->json('message', __('Failed to start ownership transfer'))]);
        }

        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function cancelOwnershipTransfer(int $circle, int $transfer): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/ownership-transfer/{$transfer}");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function acceptOwnershipTransfer(int $transfer): RedirectResponse
    {
        $this->apiClient->post("/circle-ownership-transfers/{$transfer}/accept");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    public function declineOwnershipTransfer(int $transfer): RedirectResponse
    {
        $this->apiClient->post("/circle-ownership-transfers/{$transfer}/decline");
        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }
}
