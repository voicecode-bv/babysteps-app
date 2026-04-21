<?php

use App\Services\TokenStore\SessionTokenStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

beforeEach(function () {
    $this->session = new Store('innerr', new ArraySessionHandler(60));
    $this->store = new SessionTokenStore($this->session, 'api_token');
});

it('returns null when no token is set', function () {
    expect($this->store->get())->toBeNull();
    expect($this->store->has())->toBeFalse();
});

it('stores and retrieves a token', function () {
    expect($this->store->set('abc'))->toBeTrue();
    expect($this->store->get())->toBe('abc');
    expect($this->store->has())->toBeTrue();
});

it('deletes the token', function () {
    $this->store->set('abc');

    expect($this->store->delete())->toBeTrue();
    expect($this->store->get())->toBeNull();
    expect($this->store->has())->toBeFalse();
});

it('ignores non-string values already in the session', function () {
    $this->session->put('api_token', ['not', 'a', 'string']);

    expect($this->store->get())->toBeNull();
});
