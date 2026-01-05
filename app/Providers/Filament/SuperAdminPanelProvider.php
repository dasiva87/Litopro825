<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SuperAdminMiddleware;
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

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->login()
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'primary' => Color::Purple,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->brandName('GrafiRed Super Admin')
            ->favicon(asset('favicon.ico'))
            ->brandLogo(fn () => view('components.grafired-logo'))
            ->darkModeBrandLogo(fn () => view('components.grafired-logo-dark'))
            ->resources([
                \App\Filament\SuperAdmin\Resources\PlanResource::class,
                \App\Filament\SuperAdmin\Resources\SubscriptionResource::class,
                // Enterprise Features - Fase 3 (Activando progresivamente)
                \App\Filament\SuperAdmin\Resources\PlanExperiments\PlanExperimentResource::class,
                \App\Filament\SuperAdmin\Resources\AutomatedReports\AutomatedReportResource::class,
                \App\Filament\SuperAdmin\Resources\NotificationChannels\NotificationChannelResource::class,
                \App\Filament\SuperAdmin\Resources\ApiIntegrations\ApiIntegrationResource::class,
                \App\Filament\SuperAdmin\Resources\EnterprisePlans\EnterprisePlanResource::class,
                \App\Filament\SuperAdmin\Resources\ActivityLogResource::class,
                // Temporalmente comentadas para probar el registro
                // \App\Filament\SuperAdmin\Resources\CompanyResource::class,
                // \App\Filament\SuperAdmin\Resources\UserResource::class,
            ])
            // ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\Filament\SuperAdmin\Resources')
            // ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\Filament\SuperAdmin\Pages')
            ->pages([
                \App\Filament\SuperAdmin\Pages\Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\Filament\SuperAdmin\Widgets')
            ->widgets([
                AccountWidget::class,
                // Widgets de la Fase 1 - Core Super Admin
                \App\Filament\SuperAdmin\Widgets\FinancialMetricsWidget::class,
                \App\Filament\SuperAdmin\Widgets\FailedPaymentsWidget::class,
                \App\Filament\SuperAdmin\Widgets\PlanStatsWidget::class,
                \App\Filament\SuperAdmin\Widgets\SubscriptionStatsWidget::class,
                // Widgets de la Fase 2 - Analytics Avanzados
                \App\Filament\SuperAdmin\Widgets\CohortAnalysisWidget::class,
                \App\Filament\SuperAdmin\Widgets\RevenueForecastWidget::class,
                \App\Filament\SuperAdmin\Widgets\PlanPerformanceWidget::class,
                \App\Filament\SuperAdmin\Widgets\GeographicRevenueWidget::class,
                \App\Filament\SuperAdmin\Widgets\PaymentAnalyticsWidget::class,
                // Widgets existentes temporalmente comentados
                // \App\Filament\SuperAdmin\Widgets\SystemMetricsWidget::class,
                // \App\Filament\SuperAdmin\Widgets\MrrWidget::class,
                // \App\Filament\SuperAdmin\Widgets\ChurnRateWidget::class,
                // \App\Filament\SuperAdmin\Widgets\ActiveTenantsWidget::class,
                // \App\Filament\SuperAdmin\Widgets\RevenueChartWidget::class,
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
                SuperAdminMiddleware::class, // Middleware específico para Super Admin
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->globalSearch()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->unsavedChangesAlerts()
            ->navigationGroups([
                'Tenant Management',
                'Subscription Management',
                'User Management',
                'Analytics & Reports',
                'Enterprise Features',
                'System Administration',
            ])
            ->userMenuItems([
                'admin-panel' => \Filament\Navigation\MenuItem::make()
                    ->label('Admin Panel')
                    ->url('/admin')
                    ->icon('heroicon-o-building-office'),
                'system-logs' => \Filament\Navigation\MenuItem::make()
                    ->label('System Logs')
                    ->url('/super-admin/activity-logs')
                    ->icon('heroicon-o-document-text'),
            ]);
    }
}
