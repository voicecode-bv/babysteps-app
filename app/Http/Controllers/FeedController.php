<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function __invoke(ApiClient $apiClient): Response
    {
        $response = $apiClient->get('/feed?page='.request()->integer('page', 1));

        return Inertia::render('Feed', [
            'posts' => $response->successful() ? $response->json() : ['data' => [], 'next_page_url' => null],
        ]);
    }
}
