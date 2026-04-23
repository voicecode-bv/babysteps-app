<?php

use App\Http\Middleware\HandleNativeEdge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Native\Mobile\Edge\Edge;

uses(RefreshDatabase::class);

beforeEach(function () {
    Edge::reset();

    $this->middleware = new class extends HandleNativeEdge
    {
        public int $setupCalls = 0;

        protected function setupBottomNav(Request $request): void
        {
            $this->setupCalls++;
        }
    };
});

it('does not rebuild the bottom nav when auth was cleared during the request', function () {
    $user = User::create([
        'api_user_id' => 1,
        'name' => 'Test',
        'email' => 'test@example.com',
        'username' => 'test',
        'password' => 'api-managed',
    ]);
    Auth::login($user);

    $this->middleware->handle(Request::create('/logout', 'POST'), function () {
        Auth::logout();

        return new Response('ok');
    });

    expect($this->middleware->setupCalls)->toBe(0);
});

it('skips the bottom nav entirely for guests', function () {
    $this->middleware->handle(
        Request::create('/login', 'GET'),
        fn () => new Response('ok'),
    );

    expect($this->middleware->setupCalls)->toBe(0);
});

it('builds the bottom nav for authenticated requests on regular pages', function () {
    $user = User::create([
        'api_user_id' => 2,
        'name' => 'Nav',
        'email' => 'nav@example.com',
        'username' => 'nav',
        'password' => 'api-managed',
    ]);
    Auth::login($user);

    $this->middleware->handle(
        Request::create('/', 'GET'),
        fn () => new Response('ok'),
    );

    expect($this->middleware->setupCalls)->toBe(1);
});
