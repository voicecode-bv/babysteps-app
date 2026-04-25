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

it('shows the tags page', function () {
    $this->actingAs($this->user)
        ->get('/settings/tags')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Settings/Tags'));
});

it('creates a tag through the API', function () {
    Http::fake([
        '*/tags' => Http::response(['data' => ['id' => 1, 'name' => 'travel', 'usage_count' => 0]], 201),
    ]);

    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'travel'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags')
        && $request->method() === 'POST'
        && $request['name'] === 'travel');
});

it('surfaces a validation error from the API when creating a tag', function () {
    Http::fake([
        '*/tags' => Http::response(['errors' => ['name' => ['Tag already exists.']]], 422),
    ]);

    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'travel'])
        ->assertRedirect()
        ->assertSessionHasErrors(['name' => 'Tag already exists.']);
});

it('rejects an empty tag name', function () {
    $this->actingAs($this->user)
        ->post('/tags', ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('updates a tag through the API', function () {
    Http::fake([
        '*/tags/5' => Http::response(['data' => ['id' => 5, 'name' => 'food', 'usage_count' => 2]], 200),
    ]);

    $this->actingAs($this->user)
        ->put('/tags/5', ['name' => 'food'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5')
        && $request->method() === 'PUT'
        && $request['name'] === 'food');
});

it('deletes a tag through the API', function () {
    Http::fake([
        '*/tags/5' => Http::response('', 204),
    ]);

    $this->actingAs($this->user)
        ->delete('/tags/5')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5')
        && $request->method() === 'DELETE');
});
