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
