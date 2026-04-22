<?php

use App\Services\TokenStore\SecureStorageTokenStore;
use App\Services\TokenStore\SessionTokenStore;
use App\Services\TokenStore\TokenStore;

it('picks the session store outside the NativePHP runtime under auto', function () {
    config(['api-client.token_driver' => 'auto']);

    $this->app->forgetScopedInstances();

    expect(function_exists('nativephp_call'))->toBeFalse();
    expect(app(TokenStore::class))->toBeInstanceOf(SessionTokenStore::class);
});

it('honors an explicit session driver override', function () {
    config(['api-client.token_driver' => 'session']);

    $this->app->forgetScopedInstances();

    expect(app(TokenStore::class))->toBeInstanceOf(SessionTokenStore::class);
});

it('honors an explicit secure_storage driver override', function () {
    config(['api-client.token_driver' => 'secure_storage']);

    $this->app->forgetScopedInstances();

    expect(app(TokenStore::class))->toBeInstanceOf(SecureStorageTokenStore::class);
});
