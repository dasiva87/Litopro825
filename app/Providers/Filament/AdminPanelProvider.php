<?php

namespace App\Providers\Filament;

use App\Http\Middleware\CheckActiveCompany;
use App\Http\Middleware\RedirectToHomePage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// use Andreia\FilamentNordTheme\FilamentNordThemePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('LitoPro')
            ->favicon(asset('favicon.ico'))
            ->brandLogo(fn () => view('components.litopro-logo'))
            ->darkModeBrandLogo(fn () => view('components.litopro-logo-dark'))
            // ->plugin(FilamentNordThemePlugin::make()) // Comentado para Railway
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\Home::class,
            ])
            ->default()
            ->homeUrl('admin')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Default Filament Widgets
                AccountWidget::class,

                // LitoPro Onboarding Widget (conditional display)
                \App\Filament\Widgets\OnboardingWidget::class,

                // LitoPro Central Panel Widgets (optimized load)
                \App\Filament\Widgets\DashboardStatsWidget::class,
                \App\Filament\Widgets\ActiveDocumentsWidget::class,
                // \App\Filament\Widgets\SocialFeedWidget::class, // Disabled temporarily for performance

                // LitoPro Sidebar Widgets (specialized tools)
                \App\Filament\Widgets\StockAlertsWidget::class,
                \App\Filament\Widgets\DeadlinesWidget::class,
                \App\Filament\Widgets\PaperCalculatorWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectToHomePage::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckActiveCompany::class,
            ])
            ->globalSearch()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->userMenuItems([
                'dashboard' => \Filament\Navigation\MenuItem::make()
                    ->label('Dashboard')
                    ->url('/admin/home')
                    ->icon('heroicon-o-squares-2x2'),
                'facturacion' => \Filament\Navigation\MenuItem::make()
                    ->label('Facturación')
                    ->url('/admin/billing')
                    ->icon('heroicon-o-credit-card'),
                'red-social' => \Filament\Navigation\MenuItem::make()
                    ->label('Red Social')
                    ->url('/admin/social-feed')
                    ->icon('heroicon-o-share'),
                'configuracion' => \Filament\Navigation\MenuItem::make()
                    ->label('Configuración')
                    ->url('/admin/company-settings')
                    ->icon('heroicon-o-cog-6-tooth'),
                'perfil' => \Filament\Navigation\MenuItem::make()
                    ->label('Mi Perfil')
                    ->url(fn () => '/empresa/'.auth()->user()->company->slug)
                    ->icon('heroicon-o-user-circle'),
            ]);
    }
}
