<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Inertia\Inertia;
use Inertia\Response;

class CreatePostController extends Controller
{
    public function __invoke(ApiClient $apiClient): Response
    {
        return Inertia::render('CreatePost', [
            'circles' => $apiClient->get('/circles')->json('data'),
            'defaultCircleIds' => $apiClient->get('/default-circles')->json('data') ?? [],
            'tags' => fn () => $apiClient->get('/tags')->json('data') ?? [],
        ]);
    }
}
