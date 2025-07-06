<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets as AdminWidgets;
use App\Filament\Auth\EditProfile;
use App\Filament\Common\Widgets as CommonWidgets;
use App\Http\Middleware\RedirectToLoginIfNotAuthenticated;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
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
            // ->databaseNotifications()
            // ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/pllod/theme.css')
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.components.footer'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('filament.components.hooks.user-menu-before'),
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                CommonWidgets\AccountWidget::class,
                AdminWidgets\AdminResourcesStatusOverviewWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                RedirectToLoginIfNotAuthenticated::class, //
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
