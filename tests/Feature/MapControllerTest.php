<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'api-client.token_driver' => 'session',
        'api-client.base_url' => 'https://innerr-api.test/api',
    ]);

    $this->user = User::create([
        'api_user_id' => 42,
        'name' => 'Test',
        'email' => 'test@example.com',
        'username' => 'test',
        'password' => 'api-managed',
    ]);

    $this->user->forceFill(['notifications_prompted_at' => now()])->save();

    session(['api_token' => 'test-token']);
});

it('passes the Mapbox public token fetched from the service-keys API', function () {
    Http::fake([
        '*/service-keys' => Http::response([
            'mapbox' => ['public_token' => 'pk.test-token'],
            'flare' => ['key' => null],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->get('/map')
        ->assertInertia(fn ($page) => $page
            ->component('Map')
            ->where('mapboxToken', 'pk.test-token'),
        );

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/service-keys')
        && $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer test-token'),
    );
});

it('passes a null token when the service-keys API fails', function () {
    Http::fake([
        '*/service-keys' => Http::response(['message' => 'Server error'], 500),
    ]);

    $this->actingAs($this->user)
        ->get('/map')
        ->assertInertia(fn ($page) => $page
            ->component('Map')
            ->where('mapboxToken', null),
        );
});
