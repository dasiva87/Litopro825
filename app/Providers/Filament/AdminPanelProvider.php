<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use App\Filament\Pages\Auth\PasswordReset\ResetPassword;
use App\Filament\Pages\Auth\Register;
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
            ->registration(Register::class)
            ->passwordReset(
                requestAction: RequestPasswordReset::class,
                resetAction: ResetPassword::class
            )
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('Grafired')
            ->favicon(asset('images/favicon.jpg'))
            ->brandLogo(fn () => view('components.grafired-logo'))
            ->darkModeBrandLogo(fn () => view('components.grafired-logo-dark'))
            // ->plugin(FilamentNordThemePlugin::make()) // Comentado para Railway
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->default()
            ->homeUrl('/admin')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Default Filament Widgets
                AccountWidget::class,

                // GrafiRed Onboarding Widget (removed from global - only shown in Dashboard/Home via getWidgets())
                // \App\Filament\Widgets\OnboardingWidget::class,

                // GrafiRed Central Panel Widgets (optimized load)
                \App\Filament\Widgets\DashboardStatsWidget::class,
                \App\Filament\Widgets\ActiveDocumentsWidget::class,
                \App\Filament\Widgets\SocialFeedWidget::class,

                // GrafiRed Sidebar Widgets (specialized tools)
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
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                'Documentos',
                'Items',
                'Inventario',
                'Configuración',
                'Sistema',
            ])
            ->userMenuItems([
                'perfil' => \Filament\Navigation\MenuItem::make()
                    ->label('Mi Perfil')
                    ->url(fn () => '/admin/empresa/'.auth()->user()->company->slug)
                    ->icon('heroicon-o-user-circle'),
                'users' => \Filament\Navigation\MenuItem::make()
                    ->label('Usuarios')
                    ->url('/admin/users')
                    ->icon('heroicon-o-users')
                    ->visible(fn () => auth()->user()->can('viewAny', \App\Models\User::class)),
                'roles' => \Filament\Navigation\MenuItem::make()
                    ->label('Gestión de Roles')
                    ->url('/admin/roles')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn () => auth()->user()->can('viewAny', \Spatie\Permission\Models\Role::class)),
                'configuracion' => \Filament\Navigation\MenuItem::make()
                    ->label('Configuración')
                    ->url('/admin/company-settings')
                    ->icon('heroicon-o-cog-6-tooth'),
                'facturacion' => \Filament\Navigation\MenuItem::make()
                    ->label('Facturación')
                    ->url('/admin/billing')
                    ->icon('heroicon-o-credit-card'),

            ]);
    }
}
