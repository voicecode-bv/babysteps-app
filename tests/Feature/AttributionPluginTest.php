<?php

use Innerr\Attribution\Attribution;
use Innerr\Attribution\Facades\Attribution as AttributionFacade;

it('resolves the attribution singleton from the container', function () {
    expect(app(Attribution::class))->toBeInstanceOf(Attribution::class);
});

it('no-ops gracefully when the native bridge is absent', function () {
    // nativephp_call only exists inside the native runtime; in tests it is
    // absent, so every helper must return false rather than throw.
    $attribution = new Attribution;

    expect($attribution->init())->toBeFalse()
        ->and($attribution->event('sng_complete_registration'))->toBeFalse()
        ->and($attribution->event('purchase', 5.99, 'EUR'))->toBeFalse()
        ->and($attribution->setUserId('hashed-id'))->toBeFalse();
});

it('exposes a working facade alias bound by the service provider', function () {
    expect(AttributionFacade::init())->toBeFalse();
});
