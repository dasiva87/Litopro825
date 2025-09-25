<div>
    <!-- Estilos específicos para la página home -->
    <style>
        /* Contenedor principal personalizado */
        .home-layout {
            display: flex !important;
            position: relative !important;
            min-height: calc(100vh - 64px) !important;
            background-color: #f9fafb !important;
        }

        .home-content {
            flex: 1 !important;
            padding: 24px !important;
            margin-right: 400px !important;
            overflow-y: auto !important;
        }

        .home-sidebar {
            width: 400px !important;
            padding: 20px !important;
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            z-index: 10 !important;
            min-height: 100% !important;
        }

        /* Dark theme styles */
        .dark .home-layout {
            background-color: #111827 !important;
        }

        .dark .home-content {
            background-color: #111827 !important;
        }
    </style>

    <div class="home-layout">
        <!-- Contenido Principal -->
        <div class="home-content">
            <div style="max-width: 100%; padding: 0 40px;">
                <!-- Dashboard Stats Widget -->
                <div style="margin-bottom: 24px;">
                    @livewire(\App\Filament\Widgets\DashboardStatsWidget::class)
                </div>

                <!-- Advanced Stock Alerts Widget -->
                <div style="margin-bottom: 24px;">
                    @livewire(\App\Filament\Widgets\AdvancedStockAlertsWidget::class)
                </div>

                <!-- Active Documents Widget -->
                <div style="margin-bottom: 24px;">
                    @livewire(\App\Filament\Widgets\ActiveDocumentsWidget::class)
                </div>

                <!-- Widget para Crear Post -->
                @livewire(\App\Filament\Widgets\CreatePostWidget::class)

                <!-- Widget de Social Posts -->
                @livewire(\App\Filament\Widgets\SocialPostWidget::class)
            </div>
        </div>

        <!-- Sidebar Derecho -->
        <aside class="home-sidebar">
            <div style="space-y: 24px;">
                @livewire(\App\Filament\Widgets\CalculadoraCorteWidget::class)
                @livewire(\App\Filament\Widgets\SuggestedCompaniesWidget::class)
            </div>
        </aside>
    </div>

</div>