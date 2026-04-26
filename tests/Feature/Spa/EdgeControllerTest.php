<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Voorkomt dat EdgeController een live HTTP-call naar /notifications/unread-count doet.
    Cache::put('unread_notification_count', 0, 60);
});

it('returns active tab for the home path', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/spa/edge/active-tab', ['path' => '/']);

    $response->assertOk()->assertJsonPath('active', 'home');
});

it('returns active tab for circles path', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/spa/edge/active-tab', ['path' => '/circles/12']);

    $response->assertOk()->assertJsonPath('active', 'circles');
});

it('returns active tab for settings path', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/spa/edge/active-tab', ['path' => '/settings/persons']);

    $response->assertOk()->assertJsonPath('active', 'settings');
});

it('clears the bottom nav for onboarding paths', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/spa/edge/active-tab', ['path' => '/onboarding/intro']);

    $response->assertOk()->assertJsonPath('cleared', true);
});

it('rejects requests without authentication', function () {
    $this->postJson('/api/spa/edge/active-tab', ['path' => '/'])
        ->assertStatus(401);
});

it('validates the path parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/spa/edge/active-tab', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('path');
});
