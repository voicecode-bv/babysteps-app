<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Settings/NotificationPreferences', [
            'preferences' => Inertia::defer(fn () => $this->fetchPreferences()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_liked' => ['required', 'boolean'],
            'post_commented' => ['required', 'boolean'],
            'comment_liked' => ['required', 'boolean'],
            'comment_replied' => ['required', 'boolean'],
            'new_circle_post' => ['required', 'boolean'],
            'circle_invitation_accepted' => ['required', 'boolean'],
            'circle_ownership_transfer_requested' => ['required', 'boolean'],
            'circle_ownership_transfer_accepted' => ['required', 'boolean'],
            'circle_ownership_transfer_declined' => ['required', 'boolean'],
        ]);

        $this->apiClient->put('/notification-preferences', $validated);

        return back();
    }

    /**
     * @return array<string, bool>
     */
    protected function fetchPreferences(): array
    {
        try {
            $response = $this->apiClient->get('/notification-preferences');
        } catch (ConnectionException) {
            return [];
        }

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }
}
