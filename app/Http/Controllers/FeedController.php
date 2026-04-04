<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function __invoke(ApiClient $apiClient): Response
    {
        $page = request()->integer('page', 1);
        $response = $apiClient->get('/feed?page='.$page);
        $data = $response->json();

        $paginator = new LengthAwarePaginator(
            items: $data['data'],
            total: $data['meta']['total'],
            perPage: $data['meta']['per_page'],
            currentPage: $data['meta']['current_page'],
        );

        return Inertia::render('Feed', [
            'posts' => Inertia::scroll($paginator),
            'circles' => Inertia::defer(fn () => $apiClient->get('/circles')->json('data')),
        ]);
    }
}
