<div>
    <!-- Estilos específicos para la página Gremio -->
    <style>
        /* Contenedor principal personalizado */
        .gremio-layout {
            display: flex !important;
            position: relative !important;
            min-height: calc(100vh - 64px) !important;
            background-color: #f9fafb !important;
        }

        .gremio-content {
            flex: 1 !important;
            padding: 24px !important;
            margin-right: 400px !important;
            overflow-y: auto !important;
        }

        .gremio-sidebar {
            width: 400px !important;
            padding: 20px !important;
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            z-index: 10 !important;
            min-height: 100% !important;
        }

        /* Dark theme styles */
        .dark .gremio-layout {
            background-color: #111827 !important;
        }

        .dark .gremio-content {
            background-color: #111827 !important;
        }
    </style>

    <div class="gremio-layout">
        <!-- Contenido Principal -->
        <div class="gremio-content">
            <div style="max-width: 100%; padding: 0 40px;">

                <!-- Widget para Crear Post -->
                @livewire(\App\Filament\Widgets\CreatePostWidget::class)

                <!-- Widget de Social Posts -->
                @livewire(\App\Filament\Widgets\SocialPostWidget::class)
            </div>
        </div>

        <!-- Sidebar Derecho -->
        <aside class="gremio-sidebar">
            <div style="space-y: 24px;">
                @livewire(\App\Filament\Widgets\CalculadoraCorteWidget::class)
                @livewire(\App\Filament\Widgets\SuggestedCompaniesWidget::class)
            </div>
        </aside>
    </div>

</div>