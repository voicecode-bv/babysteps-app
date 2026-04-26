<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

it('shows the persons page', function () {
    $this->actingAs($this->user)
        ->get('/settings/persons')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Settings/Persons'));
});

it('creates a person through the API', function () {
    Http::fake([
        '*/tags' => Http::response(['data' => ['id' => 1, 'type' => 'person', 'name' => 'Alice', 'birthdate' => '1990-05-12']], 201),
    ]);

    $this->actingAs($this->user)
        ->post('/persons', ['name' => 'Alice', 'birthdate' => '1990-05-12'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags')
        && $request->method() === 'POST'
        && $request['type'] === 'person'
        && $request['name'] === 'Alice'
        && $request['birthdate'] === '1990-05-12');
});

it('creates a person without a birthdate', function () {
    Http::fake([
        '*/tags' => Http::response(['data' => ['id' => 1, 'type' => 'person', 'name' => 'Bob', 'birthdate' => null]], 201),
    ]);

    $this->actingAs($this->user)
        ->post('/persons', ['name' => 'Bob'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => array_key_exists('birthdate', $request->data())
        && $request->data()['birthdate'] === null);
});

it('surfaces a validation error from the API when creating a person', function () {
    Http::fake([
        '*/tags' => Http::response(['errors' => ['name' => ['Person already exists.']]], 422),
    ]);

    $this->actingAs($this->user)
        ->post('/persons', ['name' => 'Alice'])
        ->assertRedirect()
        ->assertSessionHasErrors(['name' => 'Person already exists.']);
});

it('rejects an empty person name', function () {
    $this->actingAs($this->user)
        ->post('/persons', ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('rejects an invalid birthdate', function () {
    $this->actingAs($this->user)
        ->post('/persons', ['name' => 'Alice', 'birthdate' => '2999-01-01'])
        ->assertSessionHasErrors(['birthdate']);
});

it('updates a person through the API', function () {
    Http::fake([
        '*/tags/5' => Http::response(['data' => ['id' => 5, 'type' => 'person', 'name' => 'Alice', 'birthdate' => '1990-05-12']], 200),
    ]);

    $this->actingAs($this->user)
        ->put('/persons/5', ['name' => 'Alice', 'birthdate' => '1990-05-12'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5')
        && $request->method() === 'PUT'
        && $request['name'] === 'Alice'
        && $request['birthdate'] === '1990-05-12');
});

it('deletes a person through the API', function () {
    Http::fake([
        '*/tags/5' => Http::response('', 204),
    ]);

    $this->actingAs($this->user)
        ->delete('/persons/5')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5')
        && $request->method() === 'DELETE');
});

it('uploads a person photo through the API', function () {
    Storage::fake('local');
    Http::fake([
        '*/tags/5/avatar' => Http::response(['data' => ['id' => 5, 'avatar' => 'foo']], 200),
    ]);

    $file = File::image('photo.jpg');
    $path = $file->getPathname();

    $this->actingAs($this->user)
        ->post('/persons/5/photo', ['photo_path' => $path])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5/avatar')
        && $request->method() === 'POST');
});

it('deletes a person photo through the API', function () {
    Http::fake([
        '*/tags/5/avatar' => Http::response('', 204),
    ]);

    $this->actingAs($this->user)
        ->delete('/persons/5/photo')
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/tags/5/avatar')
        && $request->method() === 'DELETE');
});
