<?php

use App\Jobs\SyncDeviceInfo;
use App\Services\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Native\Mobile\Browser;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('api-client.base_url', 'https://api.innerr.test/api');
});

it('returns 404 for an unknown provider on start', function () {
    $this->get('/auth/social/facebook')->assertNotFound();
});

it('opens the browser session and redirects to login on start', function () {
    $browser = Mockery::mock(Browser::class);
    $browser->shouldReceive('auth')
        ->once()
        ->with('https://api.innerr.test/api/oauth/google/redirect')
        ->andReturn(true);

    $this->app->instance(Browser::class, $browser);

    $this->get('/auth/social/google')
        ->assertRedirect(route('login'));
});

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
