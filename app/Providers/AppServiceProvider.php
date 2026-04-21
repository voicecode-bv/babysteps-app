<?php

namespace App\Providers;

use App\Services\TokenStore\SecureStorageTokenStore;
use App\Services\TokenStore\SessionTokenStore;
use App\Services\TokenStore\TokenStore;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TokenStore::class, fn (Application $app) => $this->resolveTokenStore($app));
    }

    protected function resolveTokenStore(Application $app): TokenStore
    {
        $key = (string) config('api-client.token_key');
        $driver = (string) config('api-client.token_driver', 'auto');

        if ($driver === 'auto') {
            $driver = $this->detectDriver($app);
        }

        return match ($driver) {
            'session' => new SessionTokenStore($app['session.store'], $key),
            default => new SecureStorageTokenStore($key),
        };
    }

    protected function detectDriver(Application $app): string
    {
        /** @var Request $request */
        $request = $app['request'];

        return $request->hasHeader('X-NativePHP-Req-Id') ? 'secure_storage' : 'session';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
