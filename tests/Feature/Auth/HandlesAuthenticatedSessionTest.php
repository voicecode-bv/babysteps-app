<?php

use App\Http\Controllers\Auth\Concerns\HandlesAuthenticatedSession;
use App\Models\User;
use App\Services\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/**
 * Anonymous test-class to expose the trait's protected methods.
 */
beforeEach(function () {
    $this->subject = new class
    {
        use HandlesAuthenticatedSession {
            syncLocalUser as public;
            primeSettingsCache as public;
        }
    };
});

it('creates a new local user when none exists', function () {
    $this->subject->syncLocalUser([
        'id' => 42,
        'name' => 'Jane',
        'username' => 'jane',
        'email' => 'jane@example.com',
        'avatar' => 'https://example.com/a.jpg',
        'bio' => 'Hello',
        'locale' => 'nl',
        'onboarded_at' => now()->toIso8601String(),
    ]);

    $this->assertDatabaseHas('users', [
        'api_user_id' => 42,
        'username' => 'jane',
        'locale' => 'nl',
    ]);
    expect(Auth::id())->toBe(User::where('api_user_id', 42)->value('id'));
});

it('updates an existing local user when api_user_id matches', function () {
    User::factory()->create([
        'api_user_id' => 42,
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'username' => 'old',
        'locale' => 'en',
    ]);

    $this->subject->syncLocalUser([
        'id' => 42,
        'name' => 'New Name',
        'username' => 'new',
        'email' => 'new@example.com',
        'avatar' => null,
        'bio' => null,
        'locale' => 'nl',
        'onboarded_at' => null,
    ]);

    expect(User::where('api_user_id', 42)->count())->toBe(1);
    $user = User::where('api_user_id', 42)->first();
    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('new@example.com');
    expect($user->locale)->toBe('nl');
    expect($user->onboarded_at)->toBeNull();
});

it('removes stale local users with same email but different api_user_id', function () {
    User::factory()->create([
        'api_user_id' => 99,
        'email' => 'jane@example.com',
        'username' => 'jane',
    ]);

    $this->subject->syncLocalUser([
        'id' => 42,
        'name' => 'Jane',
        'username' => 'jane',
        'email' => 'jane@example.com',
        'avatar' => null,
        'bio' => null,
        'locale' => 'en',
        'onboarded_at' => null,
    ]);

    expect(User::where('api_user_id', 99)->count())->toBe(0);
    expect(User::where('api_user_id', 42)->count())->toBe(1);
});

it('prefers session locale over API locale when both present', function () {
    session(['locale' => 'nl']);

    $this->subject->syncLocalUser([
        'id' => 42,
        'name' => 'Jane',
        'username' => 'jane',
        'email' => 'jane@example.com',
        'avatar' => null,
        'bio' => null,
        'locale' => 'en',
        'onboarded_at' => null,
    ]);

    expect(User::where('api_user_id', 42)->value('locale'))->toBe('nl');
});

it('primeSettingsCache is a no-op when not authenticated', function () {
    $client = Mockery::mock(ApiClient::class);
    $client->shouldNotReceive('cachedCircles');

    $this->subject->primeSettingsCache($client);

    expect(true)->toBeTrue();
});

it('primeSettingsCache calls cachedCircles when authenticated', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $client = Mockery::mock(ApiClient::class);
    $client->shouldReceive('cachedCircles')->once()->andReturn([]);

    $this->subject->primeSettingsCache($client);
});
