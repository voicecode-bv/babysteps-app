<?php

use App\Jobs\SyncDeviceInfo;
use App\Models\User;
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

it('persists the pre-auth session locale when syncing the local user on password login', function () {
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

    $this->withSession(['locale' => 'nl'])->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ])->assertRedirect();

    expect(User::where('api_user_id', 7)->value('locale'))->toBe('nl');
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

it('removes orphan local users that collide on username or email during login', function () {
    Queue::fake();

    User::create([
        'api_user_id' => 99,
        'name' => 'Stale',
        'email' => 'stale@example.com',
        'username' => 'jane',
        'password' => 'api-managed',
    ]);

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

    $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ])->assertRedirect();

    $this->assertDatabaseMissing('users', ['api_user_id' => 99]);
    $this->assertDatabaseHas('users', [
        'api_user_id' => 7,
        'email' => 'jane@example.com',
        'username' => 'jane',
    ]);
});

it('sends a user without circles to the onboarding intro after login', function () {
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
        '*/circles' => Http::response(['data' => []], 200),
        '*' => Http::response([], 200),
    ]);

    $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ])->assertRedirect(route('onboarding.intro'));
});

it('sends a user with existing circles straight to the feed after login', function () {
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
        '*/circles' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Family'],
            ],
        ], 200),
        '*' => Http::response([], 200),
    ]);

    $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ])->assertRedirect(route('feed'));
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
