<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $this->apiClient->post('/device-token', ['token' => $validated['token']]);

        return back();
    }
}
