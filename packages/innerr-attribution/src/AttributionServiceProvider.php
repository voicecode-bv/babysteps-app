<?php

namespace Innerr\Attribution;

use Illuminate\Support\ServiceProvider;

class AttributionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Attribution::class, fn () => new Attribution);
    }
}
