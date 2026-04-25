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

it('shows the notification preferences page', function () {
    $this->actingAs($this->user)
        ->get('/settings/notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Settings/NotificationPreferences'));
});

it('updates notification preferences through the API', function () {
    Http::fake([
        '*/notification-preferences' => Http::response('', 204),
    ]);

    $payload = [
        'post_liked' => true,
        'post_commented' => false,
        'comment_liked' => true,
        'comment_replied' => false,
        'new_circle_post' => true,
        'circle_invitation_accepted' => false,
        'circle_ownership_transfer_requested' => true,
        'circle_ownership_transfer_accepted' => false,
        'circle_ownership_transfer_declined' => true,
    ];

    $this->actingAs($this->user)
        ->put('/settings/notifications', $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/notification-preferences')
        && $request->method() === 'PUT'
        && $request['post_liked'] === true);
});

it('rejects an invalid notification preference payload', function () {
    $this->actingAs($this->user)
        ->put('/settings/notifications', ['post_liked' => 'maybe'])
        ->assertSessionHasErrors(['post_liked']);
});
