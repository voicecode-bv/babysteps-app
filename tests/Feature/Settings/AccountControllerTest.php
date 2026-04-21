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

it('requests a data export through the API and returns success', function () {
    Http::fake([
        '*/account/export' => Http::response(['message' => 'Queued'], 202),
    ]);

    $this->actingAs($this->user)
        ->post('/account/export')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/account/export') && $request->method() === 'POST');
});

it('surfaces an error when the API export request fails', function () {
    Http::fake([
        '*/account/export' => Http::response(['message' => 'nope'], 500),
    ]);

    $this->actingAs($this->user)
        ->post('/account/export')
        ->assertRedirect()
        ->assertSessionHasErrors(['export']);
});
