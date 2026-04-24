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
            'posts' => Inertia::scroll(fn () => $this->paginatedPosts($apiClient, '/feed'))
                ->matchOn('data.id')
                ->defer(),
            'circles' => Inertia::defer(fn () => $apiClient->cachedCircles()),
        ]);
    }

    public function circle(int $circle, ApiClient $apiClient): Response
    {
        $circleResponse = $apiClient->get("/circles/{$circle}");

        if ($circleResponse->failed() || $circleResponse->json('data') === null) {
            abort($circleResponse->status() === 403 ? 403 : 404);
        }

        $circleData = $apiClient->proxyMediaUrls($circleResponse->json('data'));

        return Inertia::render('CircleFeed', [
            'circle' => $circleData,
            'posts' => Inertia::scroll(fn () => $this->paginatedPosts($apiClient, "/circles/{$circle}/feed"))
                ->matchOn('data.id')
                ->defer(),
        ]);
    }

    private function paginatedPosts(ApiClient $apiClient, string $path): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);

        try {
            $response = $apiClient->get($path.'?page='.$page);
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
    }
}
