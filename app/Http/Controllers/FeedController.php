<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Feed', [
            'posts' => $posts,
        ]);
    }
}
