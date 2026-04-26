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

    session(['api_token' => 'test-token']);
});

it('shows a friendly throttle message when invitations are rate limited', function () {
    Http::fake([
        '*/circles/9/members' => Http::response(['message' => 'Too Many Attempts.'], 429),
    ]);

    $this->actingAs($this->user)
        ->from('/onboarding/circles/9/invite')
        ->post('/onboarding/circles/9/invite', ['identifier' => 'friend'])
        ->assertRedirect('/onboarding/circles/9/invite')
        ->assertSessionHasErrors([
            'identifier' => __('Too many invitations sent. Please try again later.'),
        ]);
});
