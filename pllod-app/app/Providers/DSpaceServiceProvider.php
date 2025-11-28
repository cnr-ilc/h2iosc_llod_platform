<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DSpace\DSpaceAuthService;
use App\Services\DSpace\DSpaceClient;

class DSpaceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dspace.php',
            'dspace'
        );

        // Registra DSpaceAuthService come singleton
        $this->app->singleton(DSpaceAuthService::class, function ($app) {
            return new DSpaceAuthService();
        });

        // Registra DSpaceClient come singleton
        $this->app->singleton(DSpaceClient::class, function ($app) {
            return new DSpaceClient(
                $app->make(DSpaceAuthService::class)
            );
        });

        // Alias per comodità
        $this->app->alias(DSpaceClient::class, 'dspace');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Pubblica il file di configurazione
        $this->publishes([
            __DIR__ . '/../../config/dspace.php' => config_path('dspace.php'),
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
