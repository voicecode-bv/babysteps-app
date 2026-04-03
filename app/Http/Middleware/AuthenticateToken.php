<?php

namespace App\Http\Middleware;

use App\Services\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(protected ApiClient $apiClient) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if (! $this->apiClient->hasToken()) {
            return redirect()->route('login');
        }

        $result = $this->apiClient->validateToken();

        if (! $result['valid']) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
