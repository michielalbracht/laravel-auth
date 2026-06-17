<?php

namespace AlbrachtSystems\Auth;

use AlbrachtSystems\Auth\Actions\StorePasskey;
use AlbrachtSystems\Auth\Actions\VerifyPasskey;
use AlbrachtSystems\Auth\Http\Middleware\EmailGeverifieerd;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Actions\StorePasskey as BaseStorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Passkeys;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auth-module.php', 'auth-module');

        // Passkey-routes van het laravel/passkeys-pakket negeren: deze module
        // levert eigen, op de SPA afgestemde passkey-endpoints.
        Passkeys::ignoreRoutes();

        // Eigen passkey-actions met subdomein-ondersteuning injecteren.
        $this->app->bind(BaseStorePasskey::class, StorePasskey::class);
        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);
    }

    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'auth-module');

        $router->aliasMiddleware('email.geverifieerd', EmailGeverifieerd::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/auth-module.php' => config_path('auth-module.php'),
            ], 'auth-module-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/auth-module'),
            ], 'auth-module-views');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'auth-module-migrations');
        }
    }
}
