<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CompleteController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function __invoke(): RedirectResponse
    {
        $this->apiClient->post('/onboarding/complete');

        Auth::user()?->forceFill(['onboarded_at' => now()])->save();

        return redirect('/');
    }
}
