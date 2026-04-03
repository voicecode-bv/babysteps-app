<?php

namespace Babysteps\ApiClient;

use Babysteps\ApiClient\Services\ApiClient;
use Illuminate\Support\ServiceProvider;

class ApiClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-client.php', 'api-client');

        $this->app->singleton(ApiClient::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/api-client.php' => config_path('api-client.php'),
        ], 'api-client-config');
    }
}
