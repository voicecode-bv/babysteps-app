<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'username' => ['required', 'string'],
        ]);

        $response = $this->apiClient->post("/circles/{$circle}/members", $validated);

        if ($response->successful()) {
            return back();
        }

        return back()->withErrors(['username' => $response->json('message', __('Failed to add member'))]);
    }

    public function inviteMember(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $response = $this->apiClient->post("/circles/{$circle}/invitations", $validated);

        if ($response->successful()) {
            return back();
        }

        return back()->withErrors(['email' => $response->json('message', __('Failed to send invitation'))]);
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
