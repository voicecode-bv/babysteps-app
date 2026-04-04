<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(string $username, ApiClient $apiClient): Response
    {
        $page = request()->integer('page', 1);

        $profile = $apiClient->get("/profiles/{$username}")->json('data');
        $postsResponse = $apiClient->get("/profiles/{$username}/posts?page={$page}")->json();

        $paginator = new LengthAwarePaginator(
            items: $postsResponse['data'],
            total: $postsResponse['meta']['total'],
            perPage: $postsResponse['meta']['per_page'],
            currentPage: $postsResponse['meta']['current_page'],
        );

        return Inertia::render('Profile', [
            'profile' => $profile,
            'posts' => Inertia::scroll($paginator),
        ]);
    }
}
