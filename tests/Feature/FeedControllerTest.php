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

it('renders the CircleFeed page with the target circle', function () {
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
    ]);

    $this->actingAs($this->user)
        ->get('/circles/7/feed')
        ->assertInertia(fn ($page) => $page
            ->component('CircleFeed')
            ->where('circle.id', 7)
            ->where('circle.name', 'Family'),
        );
});

it('returns 403 when the user cannot view the circle', function () {
    Http::fake([
        '*/circles/7' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    $this->actingAs($this->user)
        ->get('/circles/7/feed')
        ->assertForbidden();
});

it('returns 404 when the circle does not exist', function () {
    Http::fake([
        '*/circles/99' => Http::response(['message' => 'Not found'], 404),
    ]);

    $this->actingAs($this->user)
        ->get('/circles/99/feed')
        ->assertNotFound();
});
