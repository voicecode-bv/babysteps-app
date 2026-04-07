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
        return Inertia::render('Feed', [
            'posts' => Inertia::scroll(function () use ($apiClient) {
                $page = request()->integer('page', 1);
                $response = $apiClient->get('/feed?page='.$page);

                if ($response->failed()) {
                    return new LengthAwarePaginator([], 0, 15);
                }

                $data = $response->json();

                return new LengthAwarePaginator(
                    items: $apiClient->proxyMediaUrls($data['data']),
                    total: $data['meta']['total'],
                    perPage: $data['meta']['per_page'],
                    currentPage: $data['meta']['current_page'],
                );
            }),
            'circles' => Inertia::defer(fn () => $apiClient->proxyMediaUrls($apiClient->get('/circles')->json('data'))),
        ]);
    }
}
