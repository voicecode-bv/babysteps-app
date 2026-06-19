<?php

namespace Innerr\Attribution\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool init()
 * @method static bool event(string $name, ?float $value = null, ?string $currency = null, array $attributes = [])
 * @method static bool setUserId(string $userId)
 *
 * @see \Innerr\Attribution\Attribution
 */
class Attribution extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Innerr\Attribution\Attribution::class;
    }
}
