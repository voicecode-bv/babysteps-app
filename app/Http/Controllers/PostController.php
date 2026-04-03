<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function show(Post $post): Response
    {
        $post->load([
            'user',
            'comments.user',
            'likes',
        ])->loadCount(['likes', 'comments']);

        return Inertia::render('PostDetail', [
            'post' => $post,
        ]);
    }
}
