<?php

use App\Jobs\SyncDeviceInfo;
use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('stores the token and syncs the user on a successful callback', function () {
    Queue::fake();

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('good-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn([
        'valid' => true,
        'user' => [
            'id' => 42,
            'name' => 'Jane',
            'username' => 'jane',
            'email' => 'jane@example.com',
            'avatar' => null,
            'bio' => null,
            'locale' => 'en',
        ],
    ]);
    $client->shouldReceive('cachedProfile')->andReturn(null);
    $client->shouldReceive('cachedCircles')->andReturn([]);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=good-token')
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'api_user_id' => 42,
    ]);
    $this->assertAuthenticated();
    Queue::assertPushed(SyncDeviceInfo::class);
});

it('surfaces an error when the callback contains an error query', function () {
    $this->get('/oauth/callback?error=oauth_failed')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);
});

it('surfaces an error when the callback has no token', function () {
    $this->get('/oauth/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);
});

it('clears the token and errors out when validateToken fails after storing', function () {
    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('bad-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn(['valid' => false]);
    $client->shouldReceive('clearToken')->once()->andReturn(true);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=bad-token')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('persists the pre-auth session locale when syncing the local user via social callback', function () {
    Queue::fake();

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('good-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn([
        'valid' => true,
        'user' => [
            'id' => 42,
            'name' => 'Jane',
            'username' => 'jane',
            'email' => 'jane@example.com',
            'avatar' => null,
            'bio' => null,
            'locale' => 'en',
        ],
    ]);
    $client->shouldReceive('cachedProfile')->andReturn(null);
    $client->shouldReceive('cachedCircles')->andReturn([]);

    $this->app->instance(ApiClient::class, $client);

    $this->withSession(['locale' => 'nl'])
        ->get('/oauth/callback?token=good-token')
        ->assertRedirect();

    expect(User::where('api_user_id', 42)->value('locale'))->toBe('nl');
});

it('falls back to the API-provided locale when no session locale is set during social callback', function () {
    Queue::fake();

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('good-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn([
        'valid' => true,
        'user' => [
            'id' => 42,
            'name' => 'Jane',
            'username' => 'jane',
            'email' => 'jane@example.com',
            'avatar' => null,
            'bio' => null,
            'locale' => 'nl',
        ],
    ]);
    $client->shouldReceive('cachedProfile')->andReturn(null);
    $client->shouldReceive('cachedCircles')->andReturn([]);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=good-token')->assertRedirect();

    expect(User::where('api_user_id', 42)->value('locale'))->toBe('nl');
});

it('stores the token in the session and rotates the session id when the browser completes OAuth', function () {
    Queue::fake();

    config(['api-client.token_driver' => 'session']);

    Http::fake([
        '*/auth/me' => Http::response([
            'user' => [
                'id' => 42,
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

    $this->get('/oauth/callback?token=browser-token')->assertRedirect();

    expect(session('api_token'))->toBe('browser-token');
    expect(session()->getId())->not->toBe($preLoginSessionId);
    $this->assertAuthenticated();
});
