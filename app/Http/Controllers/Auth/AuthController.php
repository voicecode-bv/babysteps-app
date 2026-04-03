<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(protected ApiClient $apiClient) {}

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->apiClient->login($validated['email'], $validated['password']);

        if (! $result['success']) {
            return back()->withErrors(['email' => $result['message']]);
        }

        $this->syncLocalUser($result['user']);

        return redirect()->route('feed');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $result = $this->apiClient->register(
            $validated['name'],
            $validated['username'],
            $validated['email'],
            $validated['password'],
        );

        if (! $result['success']) {
            return back()
                ->withErrors($result['errors'] ?? ['email' => $result['message']])
                ->withInput($request->except('password'));
        }

        $this->syncLocalUser($result['user']);

        return redirect()->route('feed');
    }

    public function logout(): RedirectResponse
    {
        $this->apiClient->logout();

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function verify(): RedirectResponse
    {
        $result = $this->apiClient->validateToken();

        if (! $result['valid']) {
            Auth::logout();

            return redirect()->route('login');
        }

        $this->syncLocalUser($result['user']);

        return redirect()->route('feed');
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    protected function syncLocalUser(array $userData): void
    {
        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'username' => $userData['username'],
                'avatar' => $userData['avatar'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'locale' => $userData['locale'] ?? config('app.locale'),
                'password' => 'api-managed',
            ],
        );

        Auth::login($user);
    }
}
