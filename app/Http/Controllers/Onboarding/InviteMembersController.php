<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class InviteMembersController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(int $circle): Response
    {
        $response = $this->apiClient->get("/circles/{$circle}");

        abort_unless($response->successful(), 404);

        $data = $response->json('data', []);

        return Inertia::render('Onboarding/InviteMembers', [
            'circle' => [
                'id' => $data['id'] ?? $circle,
                'name' => $data['name'] ?? '',
            ],
        ]);
    }

    public function store(Request $request, int $circle): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $isEmail = str_contains($validated['identifier'], '@');
        $field = $isEmail ? 'email' : 'username';
        $data = [$field => $validated['identifier']];

        $response = $this->apiClient->post("/circles/{$circle}/members", $data);

        if (! $response->successful()) {
            return back()->withErrors([
                'identifier' => $this->friendlyApiError($response->json(), $field),
            ]);
        }

        Cache::forget(ApiClient::circlesCacheKey());

        return back();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function friendlyApiError(?array $payload, string $field): string
    {
        $apiMessage = $payload['errors'][$field][0]
            ?? $payload['message']
            ?? null;

        if ($apiMessage === null) {
            return __('Failed to invite member');
        }

        $normalized = strtolower($apiMessage);

        if (str_contains($normalized, 'selected') && str_contains($normalized, 'invalid')) {
            return $field === 'email'
                ? __('No account found for this email address.')
                : __('No account found for this username.');
        }

        if (str_contains($normalized, 'already')) {
            return __('This person is already in the circle.');
        }

        return __('Failed to invite member');
    }
}
