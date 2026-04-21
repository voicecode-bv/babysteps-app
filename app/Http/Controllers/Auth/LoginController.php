<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        $apiBase = rtrim((string) config('api-client.base_url'), '/');

        return Inertia::render('Auth/Login', [
            'socialAuthUrls' => [
                'google' => $apiBase.'/oauth/google/redirect',
                'apple' => $apiBase.'/oauth/apple/redirect',
            ],
        ]);
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,nl'],
        ]);

        session(['locale' => $validated['locale']]);
        app()->setLocale($validated['locale']);

        return back();
    }
}
