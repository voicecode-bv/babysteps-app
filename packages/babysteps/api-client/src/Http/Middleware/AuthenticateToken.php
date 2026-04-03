<?php

namespace Babysteps\ApiClient\Http\Middleware;

use Babysteps\ApiClient\Services\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(protected ApiClient $apiClient) {}

    /**
     * Validate the stored API token. If invalid, clear it and redirect to login.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
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
