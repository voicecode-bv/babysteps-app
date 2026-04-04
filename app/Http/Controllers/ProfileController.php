<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(string $username, ApiClient $apiClient): Response
    {
        $profileResponse = $apiClient->get("/profiles/{$username}");

        if ($profileResponse->failed() || $profileResponse->json('data') === null) {
            abort(404);
        }

        $postsResponse = $apiClient->get("/profiles/{$username}/posts");

        return Inertia::render('Profile', [
            'profile' => $profileResponse->json('data'),
            'posts' => $postsResponse->json(),
        ]);
    }
}
