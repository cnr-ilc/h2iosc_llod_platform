<?php

namespace App\Providers;

use App\Listeners\UserLoggedOut;
use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Http\Responses\LogoutResponse;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // https://filamentphp.com/docs/3.x/support/render-hooks#available-render-hooks
        FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, fn (): string => Blade::render("@vite('resources/js/app.js')"));

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
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', Provider::class);
        });

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
