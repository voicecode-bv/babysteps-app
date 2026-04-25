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

it('returns the user tags from the API sorted by usage', function () {
    Http::fake([
        '*/tags' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'travel', 'usage_count' => 12],
                ['id' => 2, 'name' => 'food', 'usage_count' => 3],
            ],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/tags')
        ->assertOk()
        ->assertJsonPath('0.name', 'travel')
        ->assertJsonPath('1.name', 'food');
});

it('creates a tag through the API and returns the new tag', function () {
    Http::fake([
        '*/tags' => Http::response([
            'data' => ['id' => 7, 'name' => 'new-tag', 'usage_count' => 0],
        ], 201),
    ]);

    $this->actingAs($this->user)
        ->postJson('/tags', ['name' => 'new-tag'])
        ->assertCreated()
        ->assertJsonPath('id', 7)
        ->assertJsonPath('name', 'new-tag');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags')
        && $request->method() === 'POST'
        && $request['name'] === 'new-tag'
    );
});

it('forwards API validation errors when creating a tag fails', function () {
    Http::fake([
        '*/tags' => Http::response([
            'message' => 'The name has already been taken.',
            'errors' => ['name' => ['The name has already been taken.']],
        ], 422),
    ]);

    $this->actingAs($this->user)
        ->postJson('/tags', ['name' => 'duplicate'])
        ->assertStatus(422)
        ->assertJsonPath('errors.name.0', 'The name has already been taken.');
});

it('lowercases the tag name before sending it to the API', function () {
    Http::fake([
        '*/tags' => Http::response([
            'data' => ['id' => 8, 'name' => 'travel', 'usage_count' => 0],
        ], 201),
    ]);

    $this->actingAs($this->user)
        ->postJson('/tags', ['name' => 'TRAVEL'])
        ->assertCreated();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags')
        && $request->method() === 'POST'
        && $request['name'] === 'travel'
    );
});

it('rejects empty tag names locally', function () {
    $this->actingAs($this->user)
        ->postJson('/tags', ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
