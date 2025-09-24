<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el servicio de movimientos de stock
        $this->app->singleton(\App\Services\StockMovementService::class, function ($app) {
            return new \App\Services\StockMovementService();
        });

        // Registrar servicios de alertas y notificaciones
        $this->app->singleton(\App\Services\StockAlertService::class, function ($app) {
            return new \App\Services\StockAlertService();
        });

        $this->app->singleton(\App\Services\StockReportService::class, function ($app) {
            return new \App\Services\StockReportService();
        });

        $this->app->singleton(\App\Services\StockPredictionService::class, function ($app) {
            return new \App\Services\StockPredictionService();
        });

        $this->app->singleton(\App\Services\StockNotificationService::class, function ($app) {
            return new \App\Services\StockNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
