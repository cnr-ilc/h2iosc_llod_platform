<?php

namespace App\Providers;

use App\Services\SemanticTurkeyService;
use Illuminate\Support\ServiceProvider;

class SemanticTurkeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('semanticTurkey', function ($app): SemanticTurkeyService {
            return new SemanticTurkeyService(
                config('semanticTurkey.base_url'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/semanticTurkey.php' => config_path('semanticTurkey.php'),
        ], 'semanticTurkey-config');
    }
}
