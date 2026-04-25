<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    Cache::put('circles', [
        ['id' => 1, 'name' => 'Family', 'members_count' => 3],
    ]);
});

it('shows the default circles page', function () {
    $this->actingAs($this->user)
        ->get('/settings/default-circles')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/DefaultCircles')
            ->has('circles'));
});

it('updates default circles through the API', function () {
    Http::fake([
        '*/default-circles' => Http::response('', 204),
    ]);

    $this->actingAs($this->user)
        ->put('/settings/default-circles', ['circle_ids' => [1, 2]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/default-circles')
        && $request->method() === 'PUT'
        && $request['circle_ids'] === [1, 2]);
});

it('requires the circle_ids key when updating default circles', function () {
    $this->actingAs($this->user)
        ->put('/settings/default-circles', [])
        ->assertSessionHasErrors(['circle_ids']);
});
