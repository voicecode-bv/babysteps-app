<?php

use App\Services\TokenStore\SecureStorageTokenStore;
use App\Services\TokenStore\SessionTokenStore;
use App\Services\TokenStore\TokenStore;

it('picks the session store for regular browser requests under auto', function () {
    config(['api-client.token_driver' => 'auto']);

    $this->app->forgetScopedInstances();
    $this->app->instance('request', request()->duplicate());

    expect(app(TokenStore::class))->toBeInstanceOf(SessionTokenStore::class);
});

it('picks the secure storage store when the NativePHP header is present under auto', function () {
    config(['api-client.token_driver' => 'auto']);

    $request = request()->duplicate();
    $request->headers->set('X-NativePHP-Req-Id', 'abc');

    $this->app->forgetScopedInstances();
    $this->app->instance('request', $request);

    expect(app(TokenStore::class))->toBeInstanceOf(SecureStorageTokenStore::class);
});

it('honors an explicit session driver override', function () {
    config(['api-client.token_driver' => 'session']);

    $request = request()->duplicate();
    $request->headers->set('X-NativePHP-Req-Id', 'abc');

    $this->app->forgetScopedInstances();
    $this->app->instance('request', $request);

    expect(app(TokenStore::class))->toBeInstanceOf(SessionTokenStore::class);
});

it('honors an explicit secure_storage driver override', function () {
    config(['api-client.token_driver' => 'secure_storage']);

    $this->app->forgetScopedInstances();
    $this->app->instance('request', request()->duplicate());

    expect(app(TokenStore::class))->toBeInstanceOf(SecureStorageTokenStore::class);
});
