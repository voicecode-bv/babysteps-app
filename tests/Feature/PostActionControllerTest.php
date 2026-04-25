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

it('forwards caption and circle ids to the API when updating a post', function () {
    Http::fake([
        '*/posts/5' => Http::response(['data' => ['id' => 5]], 200),
    ]);

    $this->actingAs($this->user)
        ->put('/posts/5', [
            'caption' => 'Updated caption',
            'circle_ids' => [1, 2],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/posts/5')
        && $request->method() === 'PUT'
        && $request['caption'] === 'Updated caption'
        && $request['circle_ids'] === [1, 2]
        && $request['tag_ids'] === []
    );
});

it('forwards tag ids to the API when updating a post', function () {
    Http::fake([
        '*/posts/5' => Http::response(['data' => ['id' => 5]], 200),
    ]);

    $this->actingAs($this->user)
        ->put('/posts/5', [
            'caption' => 'Tagged caption',
            'circle_ids' => [1],
            'tag_ids' => [11, 22],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && $request['tag_ids'] === [11, 22]
    );
});

it('surfaces an error when the API update request fails', function () {
    Http::fake([
        '*/posts/5' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    $this->actingAs($this->user)
        ->put('/posts/5', [
            'caption' => 'whatever',
            'circle_ids' => [1],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['caption']);
});

it('validates that at least one circle is selected on update', function () {
    $this->actingAs($this->user)
        ->put('/posts/5', [
            'caption' => 'whatever',
            'circle_ids' => [],
        ])
        ->assertSessionHasErrors(['circle_ids']);
});

it('returns the paginated likes list from the API', function () {
    Http::fake([
        '*/posts/5/likes*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Alice', 'username' => 'alice', 'avatar' => null],
                ['id' => 2, 'name' => 'Bob', 'username' => 'bob', 'avatar' => null],
            ],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->getJson('/posts/5/likes')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.username', 'alice')
        ->assertJsonPath('data.1.username', 'bob');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/posts/5/likes')
        && $request->method() === 'GET'
    );
});

it('surfaces an error when the likes API request fails', function () {
    Http::fake([
        '*/posts/5/likes*' => Http::response(['message' => 'Boom'], 500),
    ]);

    $this->actingAs($this->user)
        ->getJson('/posts/5/likes')
        ->assertStatus(500)
        ->assertJsonPath('message', 'Boom');
});
