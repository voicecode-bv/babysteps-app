<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class MediaProxyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $url = $request->query('url');

        abort_unless($url, 400);

        $cacheKey = 'media_'.md5($url);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return response($cached['body'], 200, [
                'Content-Type' => $cached['content_type'],
                'Content-Length' => strlen($cached['body']),
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        $response = Http::withoutVerifying()
            ->timeout(15)
            ->get($url);

        abort_unless($response->successful(), 404);

        $body = $response->body();
        $contentType = $response->header('Content-Type');

        Cache::put($cacheKey, [
            'body' => $body,
            'content_type' => $contentType,
        ], now()->addDays(7));

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => strlen($body),
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
