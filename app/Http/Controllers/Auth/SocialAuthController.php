<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectToCallback([
                'error' => $request->string('error')->toString(),
            ]);
        }

        $token = $request->string('token')->toString();

        if ($token === '') {
            return $this->redirectToCallback(['error' => 'missing_token']);
        }

        // Validatie hier zodat we duidelijk falen voordat de auth-sheet sluit.
        // De SPA krijgt het token via de deeplink en bewaart het zelf in de
        // Keychain — de WKWebView en deze auth-sessie delen geen cookies.
        $this->apiClient->storeToken($token);
        $result = $this->apiClient->validateToken();
        $this->apiClient->clearToken();

        if (! ($result['valid'] ?? false)) {
            return $this->redirectToCallback(['error' => 'invalid_token']);
        }

        return $this->redirectToCallback(['token' => $token]);
    }

    /**
     * @param  array<string, string>  $params
     */
    protected function redirectToCallback(array $params): RedirectResponse
    {
        $scheme = config('nativephp.deeplink_scheme');

        if (! is_string($scheme) || $scheme === '') {
            // Geen scheme geconfigureerd (bijv. web-omgeving) → val terug op
            // een interne route zodat de browser-flow blijft werken.
            return isset($params['error'])
                ? redirect('/login?oauth_error='.urlencode($params['error']))
                : redirect('/?oauth=success');
        }

        return redirect($scheme.'://oauth-callback?'.http_build_query($params));
    }
}
