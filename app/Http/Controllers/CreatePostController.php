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
        ]);
    }
}
