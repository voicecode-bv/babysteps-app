<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
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

                try {
                    $response = $apiClient->get('/feed?page='.$page);
                } catch (ConnectionException) {
                    return new LengthAwarePaginator([], 0, 10, $page);
                }

                if ($response->failed()) {
                    return new LengthAwarePaginator([], 0, 10, $page);
                }

                $data = $response->json();

                return new LengthAwarePaginator(
                    items: $apiClient->proxyMediaUrls($data['data']),
                    total: $data['meta']['total'],
                    perPage: $data['meta']['per_page'],
                    currentPage: $data['meta']['current_page'],
                );
            })->matchOn('data.id'),
            'circles' => Inertia::defer(fn () => $apiClient->cachedCircles()),
        ]);
    }
}
