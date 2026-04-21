<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class FirstCircleController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function show(): Response
    {
        return Inertia::render('Onboarding/FirstCircle');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $response = $this->apiClient->post('/circles', $validated);

        if (! $response->successful()) {
            return back()->withErrors(['name' => $response->json('message', __('Failed to create circle'))]);
        }

        Cache::forget(ApiClient::circlesCacheKey());

        $circleId = (int) $response->json('data.id');

        return redirect()->route('onboarding.invite-members', $circleId);
    }
}
