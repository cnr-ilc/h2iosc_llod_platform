<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfile;
use App\Filament\Common\Widgets as CommonWidgets;
use App\Filament\User\Pages as CustomPages;
use App\Filament\User\Widgets as UserWidgets;
use App\Http\Middleware\RedirectToLoginIfNotAuthenticated;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('dashboard')
            ->brandLogo(fn () => view('filament.components.logo'))
            ->brandLogoHeight('auto')
            ->colors([
                'primary' => Color::Gray,
            ])
            ->darkMode(false)
            ->profile(page: EditProfile::class, isSimple: false)
            ->font(family: 'Helvetica Neue', provider: GoogleFontProvider::class)
            ->topNavigation()
            ->unsavedChangesAlerts()
            ->spa()
            ->viteTheme('resources/css/filament/pllod/theme.css')
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.components.footer'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('filament.components.hooks.user-menu-before'),
            )
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->pages([
                Dashboard::class,
                CustomPages\ImportResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->widgets([
                CommonWidgets\AccountWidget::class,
                UserWidgets\UserResourcesStatusOverviewWidget::class,
                UserWidgets\UserResourcesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                RedirectToLoginIfNotAuthenticated::class, //
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
