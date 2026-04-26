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

it('shows a friendly throttle message when adding a member returns 429', function () {
    Http::fake([
        '*/circles/7/members' => Http::response(['message' => 'Too Many Attempts.'], 429),
    ]);

    $this->actingAs($this->user)
        ->from('/circles/7')
        ->post('/circles/7/members', ['identifier' => 'jane@example.com'])
        ->assertRedirect('/circles/7')
        ->assertSessionHasErrors([
            'identifier' => __('Too many invitations sent. Please try again later.'),
        ]);
});

it('still surfaces upstream message for non-429 failures', function () {
    Http::fake([
        '*/circles/7/members' => Http::response(['message' => 'Member already exists'], 422),
    ]);

    $this->actingAs($this->user)
        ->from('/circles/7')
        ->post('/circles/7/members', ['identifier' => 'jane'])
        ->assertRedirect('/circles/7')
        ->assertSessionHasErrors(['identifier' => 'Member already exists']);
});
