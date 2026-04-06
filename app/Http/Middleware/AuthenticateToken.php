<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(protected ApiClient $apiClient) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->api_user_id) {
            return $next($request);
        }

        if (! $this->apiClient->hasToken()) {
            return redirect()->route('login');
        }

        $result = $this->apiClient->validateToken();

        if (! $result['valid']) {
            return redirect()->route('login');
        }

        $userData = $result['user'];

        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'api_user_id' => $userData['id'],
                'name' => $userData['name'],
                'username' => $userData['username'],
                'avatar' => $userData['avatar'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'locale' => $userData['locale'] ?? config('app.locale'),
                'password' => 'api-managed',
            ],
        );

        Auth::login($user);

        if ($user->locale) {
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
