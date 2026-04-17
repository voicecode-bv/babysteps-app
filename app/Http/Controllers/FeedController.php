<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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
                    return new LengthAwarePaginator([], 0, 10);
                }

                $data = $response->json();

                return new LengthAwarePaginator(
                    items: $apiClient->proxyMediaUrls($data['data']),
                    total: $data['meta']['total'],
                    perPage: $data['meta']['per_page'],
                    currentPage: $data['meta']['current_page'],
                );
            }),
            'circles' => Inertia::defer(function () use ($apiClient) {
                $cached = Cache::get('circles');

                if ($cached !== null) {
                    return $cached;
                }

                try {
                    $response = $apiClient->get('/circles');
                    $circles = $response->successful()
                        ? $apiClient->proxyMediaUrls($response->json('data'))
                        : [];
                } catch (ConnectionException) {
                    return [];
                }

                Cache::put('circles', $circles, 300);

                return $circles;
            }),
        ]);
    }
}
