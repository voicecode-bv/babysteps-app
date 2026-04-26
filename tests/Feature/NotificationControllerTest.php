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

it('forwards the page query string to the upstream notifications endpoint', function () {
    Http::fake([
        '*/notifications?page=2' => Http::response([
            'data' => [[
                'id' => 'n-1',
                'type' => 'post-liked',
                'data' => [],
                'read_at' => null,
                'created_at' => '2026-04-26T10:00:00Z',
            ]],
            'meta' => ['current_page' => 2, 'last_page' => 5, 'per_page' => 20, 'total' => 100],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/notifications/load?page=2')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 5)
        ->assertJsonCount(1, 'data');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/notifications?page=2'));
});

it('clamps invalid page numbers to 1', function () {
    Http::fake([
        '*/notifications?page=1' => Http::response([
            'data' => [],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/notifications/load?page=0')
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'page=1'));
});

it('returns the upstream error status when loading notifications fails', function () {
    Http::fake([
        '*/notifications*' => Http::response(['message' => 'Server exploded'], 500),
    ]);

    $this->actingAs($this->user)
        ->getJson('/notifications/load?page=2')
        ->assertStatus(500)
        ->assertJsonPath('message', 'Server exploded');
});
