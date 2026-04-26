<?php

use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tempPath = storage_path('app/private/'.uniqid('test_avatar_', true).'.jpg');
    @mkdir(dirname($this->tempPath), 0755, true);
    file_put_contents($this->tempPath, base64_decode(
        '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQ'.
        'EBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEB'.
        'AQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABA'.
        'QAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP'.
        '/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AKpgB//Z'
    ));
});

afterEach(function () {
    @unlink($this->tempPath);
});

it('rejects avatar upload without auth', function () {
    $this->postJson('/api/spa/settings/profile/avatar', [
        'avatar_path' => '/tmp/x.jpg',
    ])->assertStatus(401);
});

it('returns 422 when avatar_path does not exist on disk', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/spa/settings/profile/avatar', [
            'avatar_path' => '/non/existent/avatar.jpg',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar_path');
});

it('uploads avatar and persists URL on local user', function () {
    $user = User::factory()->create();

    $apiResponse = new Response(Http::response([
        'user' => ['avatar' => 'https://api.example.com/avatars/42.jpg'],
    ], 200)->wait());

    $pending = Mockery::mock(PendingRequest::class);
    $pending->shouldReceive('attach')->once()->andReturnSelf();
    $pending->shouldReceive('post')->once()->with('/profile/avatar')->andReturn($apiResponse);

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('authenticated')->andReturn($pending);
    $this->app->instance(ApiClient::class, $client);

    $response = $this->actingAs($user)
        ->postJson('/api/spa/settings/profile/avatar', [
            'avatar_path' => $this->tempPath,
        ]);

    $response->assertOk()->assertJsonPath('avatar', 'https://api.example.com/avatars/42.jpg');

    expect($user->fresh()->avatar)->toBe('https://api.example.com/avatars/42.jpg');
});
