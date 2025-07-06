<?php

namespace App\Providers;

use App\Listeners\UserLoggedOut;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Http\Responses\Auth\LogoutResponse;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // https://filamentphp.com/docs/3.x/support/render-hooks#available-render-hooks
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn(): string => Blade::render("@vite('resources/js/app.js')"));

        $this->app->singleton(
            LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        $this->app->singleton(
            LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/filament/components'), 'filament');

        Event::listen('Illuminate\Auth\Events\Logout', UserLoggedOut::class);
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
