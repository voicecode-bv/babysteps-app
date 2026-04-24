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

it('renders the ProfileMap page with the target profile and mapbox token', function () {
    Http::fake([
        '*/profiles/alice' => Http::response([
            'data' => [
                'id' => 99,
                'name' => 'Alice',
                'username' => 'alice',
                'avatar' => null,
                'bio' => null,
                'created_at' => '2026-01-01T00:00:00Z',
                'posts_count' => 3,
            ],
        ], 200),
        '*/service-keys' => Http::response([
            'mapbox' => ['public_token' => 'pk.test-token'],
            'flare' => ['key' => null],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->get('/profiles/alice/map')
        ->assertInertia(fn ($page) => $page
            ->component('ProfileMap')
            ->where('mapboxToken', 'pk.test-token')
            ->where('profile.username', 'alice')
            ->where('profile.posts_count', 3),
        );
});

it('returns 404 when the target profile does not exist', function () {
    Http::fake([
        '*/profiles/ghost' => Http::response(['message' => 'Not found'], 404),
    ]);

    $this->actingAs($this->user)
        ->get('/profiles/ghost/map')
        ->assertNotFound();
});

it('proxies the profile photos endpoint to the api', function () {
    Http::fake([
        '*/profiles/alice/photos/map*' => Http::response([
            'type' => 'FeatureCollection',
            'truncated' => false,
            'features' => [],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/profiles/alice/photos/map?bbox=4.7,52.3,5.0,52.5&media_type=all')
        ->assertSuccessful()
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonPath('features', []);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/profiles/alice/photos/map')
        && str_contains($request->url(), 'bbox=4.7%2C52.3%2C5.0%2C52.5')
        && str_contains($request->url(), 'media_type=all')
        && $request->hasHeader('Authorization', 'Bearer test-token'),
    );
});

it('returns an empty FeatureCollection when the profile photos api fails', function () {
    Http::fake([
        '*/profiles/alice/photos/map*' => Http::response(['message' => 'Server error'], 500),
    ]);

    $this->actingAs($this->user)
        ->getJson('/profiles/alice/photos/map?bbox=4.7,52.3,5.0,52.5')
        ->assertStatus(500)
        ->assertJsonPath('type', 'FeatureCollection')
        ->assertJsonPath('features', []);
});

it('validates bbox on the profile photos proxy', function () {
    $this->actingAs($this->user)
        ->getJson('/profiles/alice/photos/map')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['bbox']);
});

it('renders the CircleMap page with the target circle and mapbox token', function () {
    Http::fake([
        '*/circles/7' => Http::response([
            'data' => [
                'id' => 7,
                'name' => 'Family',
                'photo' => null,
                'is_owner' => true,
                'members_count' => 5,
            ],
        ], 200),
        '*/service-keys' => Http::response([
            'mapbox' => ['public_token' => 'pk.test-token'],
            'flare' => ['key' => null],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->get('/circles/7/map')
        ->assertInertia(fn ($page) => $page
            ->component('CircleMap')
            ->where('mapboxToken', 'pk.test-token')
            ->where('circle.id', 7)
            ->where('circle.name', 'Family'),
        );
});

it('returns 403 when the user cannot view the circle', function () {
    Http::fake([
        '*/circles/7' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    $this->actingAs($this->user)
        ->get('/circles/7/map')
        ->assertForbidden();
});

it('returns 404 when the circle does not exist', function () {
    Http::fake([
        '*/circles/99' => Http::response(['message' => 'Not found'], 404),
    ]);

    $this->actingAs($this->user)
        ->get('/circles/99/map')
        ->assertNotFound();
});

it('proxies the circle photos endpoint to the api', function () {
    Http::fake([
        '*/circles/7/photos/map*' => Http::response([
            'type' => 'FeatureCollection',
            'truncated' => false,
            'features' => [],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/circles/7/photos/map?bbox=4.7,52.3,5.0,52.5&media_type=all')
        ->assertSuccessful()
        ->assertJsonPath('type', 'FeatureCollection');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/circles/7/photos/map')
        && str_contains($request->url(), 'media_type=all')
        && $request->hasHeader('Authorization', 'Bearer test-token'),
    );
});
