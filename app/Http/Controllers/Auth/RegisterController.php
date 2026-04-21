<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function show(): Response
    {
        $apiBase = rtrim((string) config('api-client.base_url'), '/');

        return Inertia::render('Auth/Register', [
            'socialAuthUrls' => [
                'google' => $apiBase.'/oauth/google/redirect',
                'apple' => $apiBase.'/oauth/apple/redirect',
            ],
        ]);
    }
}
