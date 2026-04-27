<?php

use App\Services\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['nativephp.deeplink_scheme' => 'innerrapp']);
});

it('redirects to the deeplink with the token on a successful callback', function () {
    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('good-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn(['valid' => true]);
    $client->shouldReceive('clearToken')->once()->andReturn(true);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=good-token')
        ->assertRedirect('innerrapp://oauth-callback?token=good-token');
});

it('redirects to the deeplink with the error code when the callback carries an error', function () {
    $this->get('/oauth/callback?error=oauth_failed')
        ->assertRedirect('innerrapp://oauth-callback?error=oauth_failed');
});

it('redirects to the deeplink with missing_token when no token is provided', function () {
    $this->get('/oauth/callback')
        ->assertRedirect('innerrapp://oauth-callback?error=missing_token');
});

it('redirects to the deeplink with invalid_token when validateToken fails', function () {
    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('bad-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn(['valid' => false]);
    $client->shouldReceive('clearToken')->once()->andReturn(true);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=bad-token')
        ->assertRedirect('innerrapp://oauth-callback?error=invalid_token');
});

it('falls back to a web redirect when no deeplink scheme is configured', function () {
    config(['nativephp.deeplink_scheme' => null]);

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('storeToken')->once()->with('good-token')->andReturn(true);
    $client->shouldReceive('validateToken')->once()->andReturn(['valid' => true]);
    $client->shouldReceive('clearToken')->once()->andReturn(true);

    $this->app->instance(ApiClient::class, $client);

    $this->get('/oauth/callback?token=good-token')
        ->assertRedirect('/?oauth=success');
});

it('falls back to /login with the error query when no deeplink scheme is configured', function () {
    config(['nativephp.deeplink_scheme' => null]);

    $this->get('/oauth/callback?error=oauth_failed')
        ->assertRedirect('/login?oauth_error=oauth_failed');
});
