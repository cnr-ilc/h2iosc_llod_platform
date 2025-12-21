<?php

namespace App\Providers;

use App\Services\DSpace\DSpaceAuthService;
use App\Services\DSpace\DSpaceClient;
use Illuminate\Support\ServiceProvider;

class DSpaceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/dspace.php',
            'dspace'
        );

        $this->app->singleton(DSpaceAuthService::class, function ($app) {
            return new DSpaceAuthService;
        });

        $this->app->singleton(DSpaceClient::class, function ($app) {
            return new DSpaceClient(
                $app->make(DSpaceAuthService::class)
            );
        });

        $this->app->alias(DSpaceClient::class, 'dspace');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/dspace.php' => config_path('dspace.php'),
        ], 'dspace-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DSpaceAuthService::class,
            DSpaceClient::class,
            'dspace',
        ];
    }
}
