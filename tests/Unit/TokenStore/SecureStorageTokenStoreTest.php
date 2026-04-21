<?php

use App\Services\TokenStore\SecureStorageTokenStore;
use Native\Mobile\Facades\SecureStorage;

beforeEach(function () {
    $this->store = new SecureStorageTokenStore('api_token');
});

it('reads the token through the SecureStorage facade', function () {
    SecureStorage::shouldReceive('get')->once()->with('api_token')->andReturn('abc');

    expect($this->store->get())->toBe('abc');
});

it('stores the token through the SecureStorage facade', function () {
    SecureStorage::shouldReceive('set')->once()->with('api_token', 'abc')->andReturn(true);

    expect($this->store->set('abc'))->toBeTrue();
});

it('deletes the token through the SecureStorage facade', function () {
    SecureStorage::shouldReceive('delete')->once()->with('api_token')->andReturn(true);

    expect($this->store->delete())->toBeTrue();
});

it('reports has() based on get()', function () {
    SecureStorage::shouldReceive('get')->once()->with('api_token')->andReturn('abc');
    expect($this->store->has())->toBeTrue();

    SecureStorage::shouldReceive('get')->once()->with('api_token')->andReturn(null);
    expect($this->store->has())->toBeFalse();
});
