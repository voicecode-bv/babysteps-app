<?php

use App\Jobs\SyncDeviceInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'api-client.token_driver' => 'session',
        'api-client.base_url' => 'https://innerr-api.test/api',
    ]);
});

it('logs a browser user in by storing the API token in the session', function () {
    Queue::fake();

    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'browser-session-token',
            'user' => [
                'id' => 7,
                'name' => 'Jane',
                'username' => 'jane',
                'email' => 'jane@example.com',
                'avatar' => null,
                'bio' => null,
                'locale' => 'en',
            ],
        ], 200),
        '*' => Http::response([], 200),
    ]);

    $this->startSession();
    $preLoginSessionId = session()->getId();

    $response = $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect();
    expect(session('api_token'))->toBe('browser-session-token');
    expect(session()->getId())->not->toBe($preLoginSessionId);
    $this->assertAuthenticated();
    Queue::assertPushed(SyncDeviceInfo::class);
});

it('registers a browser user and stores the API token in the session', function () {
    Queue::fake();

    Http::fake([
        '*/auth/register' => Http::response([
            'token' => 'browser-session-token',
            'user' => [
                'id' => 8,
                'name' => 'Alex',
                'username' => 'alex',
                'email' => 'alex@example.com',
                'avatar' => null,
                'bio' => null,
                'locale' => 'en',
            ],
        ], 200),
        '*' => Http::response([], 200),
    ]);

    $response = $this->post('/register', [
        'name' => 'Alex',
        'username' => 'alex',
        'email' => 'alex@example.com',
        'password' => 'correct-horse-battery-staple',
        'terms_accepted' => true,
    ]);

    $response->assertRedirect();
    expect(session('api_token'))->toBe('browser-session-token');
    $this->assertAuthenticated();
});

it('surfaces API errors on login without creating a session token', function () {
    Http::fake([
        '*/auth/login' => Http::response(['message' => 'Invalid credentials'], 401),
    ]);

    $this->post('/login', [
        'email' => 'wrong@example.com',
        'password' => 'nope',
    ])->assertSessionHasErrors(['email']);

    expect(session('api_token'))->toBeNull();
    $this->assertGuest();
});

it('clears the session token and deletes the local user on logout', function () {
    Queue::fake();

    Http::fake([
        '*/auth/login' => Http::response([
            'token' => 'browser-session-token',
            'user' => [
                'id' => 7,
                'name' => 'Jane',
                'username' => 'jane',
                'email' => 'jane@example.com',
                'avatar' => null,
                'bio' => null,
                'locale' => 'en',
            ],
        ], 200),
        '*/auth/logout' => Http::response([], 200),
        '*' => Http::response([], 200),
    ]);

    $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);

    expect(session('api_token'))->toBe('browser-session-token');
    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

    $this->post('/logout')->assertRedirect(route('login'));

    expect(session('api_token'))->toBeNull();
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
});
