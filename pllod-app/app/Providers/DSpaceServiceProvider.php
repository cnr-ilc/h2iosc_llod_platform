<?php

namespace App\Providers;

use App\Services\DSpaceService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class DSpaceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('dspace', function ($app) {
            return new DSpaceService(
                config('dspace.base_url'),
                new Client(['verify' => false]),
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/dspace.php' => config_path('dspace.php'),
        ], 'dspace-config');
    }
}
