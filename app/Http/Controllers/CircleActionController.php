<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            return back();
        }

        return back()->withErrors(['name' => $response->json('message', __('Failed to update circle'))]);
    }

    public function destroy(int $circle): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}");

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
            return back();
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

        return back();
    }

    public function deletePhoto(int $circle): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/photo");

        return back();
    }

    public function acceptInvitation(int $invitation): RedirectResponse
    {
        $this->apiClient->post("/circle-invitations/{$invitation}/accept");

        return back();
    }

    public function declineInvitation(int $invitation): RedirectResponse
    {
        $this->apiClient->post("/circle-invitations/{$invitation}/decline");

        return back();
    }

    public function cancelInvitation(int $circle, int $invitation): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/invitations/{$invitation}");

        return back();
    }

    public function removeMember(int $circle, int $user): RedirectResponse
    {
        $this->apiClient->delete("/circles/{$circle}/members/{$user}");

        return back();
    }
}
