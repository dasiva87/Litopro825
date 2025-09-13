<div>
    <!-- Override completo de Filament + Topbar personalizado -->
    <style>
        /* Ocultar topbar original de Filament */
        .fi-topbar {
            display: none !important;
        }

        /* Ajustes mínimos para el layout */
        body {
            overflow-x: hidden !important;
        }

        .fi-main {
            position: relative !important;
        }

        .fi-page-content {
            position: relative !important;
        }

        /* Topbar personalizado */
        .custom-topbar {
            background: white !important;
            border-bottom: 1px solid #e5e7eb !important;
            padding: 12px 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 100 !important;
            height: 64px !important;
        }

        /* Contenedor principal personalizado */
        .home-layout {
            display: flex !important;
            position: relative !important;
            margin-top: 64px !important;
            min-height: calc(100vh - 64px - 60px) !important;
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
            background-color: #22c55e !important;
            padding: 20px !important;
            position: fixed !important;
            right: 0 !important;
            top: 64px !important;
            bottom: 0 !important;
            z-index: 10 !important;
            overflow-y: auto !important;
        }
    </style>

    <!-- Topbar personalizado -->
    <div class="custom-topbar">
        <!-- Logo LitoPro -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 24px; height: 24px; color: white;" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 18px; font-weight: 700; color: #111827; line-height: 1;">LitoPro</div>
                <div style="font-size: 12px; color: #6b7280; line-height: 1;">Panel de Control</div>
            </div>
        </div>

        <!-- Barra de búsqueda -->
        <div style="flex: 1; max-width: 600px; margin: 0 32px;">
            <div style="position: relative;">
                <svg style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    placeholder="Buscar cotizaciones, clientes, órdenes..."
                    style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid #d1d5db; border-radius: 24px; font-size: 14px; background: #f9fafb; color: #374151;"
                >
            </div>
        </div>

        <!-- Botones de navegación y usuario -->
        <div style="display: flex; align-items: center; gap: 16px;">
            <!-- Dashboard Button -->
            <button style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #eff6ff; color: #3b82f6; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Dashboard
            </button>

            <!-- Red Social Button -->
            <button style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f3f4f6; color: #6b7280; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                </svg>
                Red Social
            </button>

            <!-- Notificaciones -->
            <div style="position: relative;">
                <button style="padding: 8px; background: transparent; border: none; border-radius: 8px; cursor: pointer;">
                    <svg style="width: 20px; height: 20px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM21 12.3c-.6 0-1-.4-1-1V8.5c0-1.4-.6-2.8-1.6-3.7-1-.9-2.4-1.4-3.8-1.4s-2.8.5-3.8 1.4c-1 .9-1.6 2.3-1.6 3.7v2.8c0 .6-.4 1-1 1s-1-.4-1-1V8.5c0-2.1.8-4.1 2.3-5.6C10.9 1.4 12.9.6 15 .6s4.1.8 5.6 2.3c1.5 1.5 2.3 3.5 2.3 5.6v2.8c0 .6-.4 1-1 1z"/>
                    </svg>
                </button>
                <div style="position: absolute; top: 4px; right: 4px; width: 18px; height: 18px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600;">3</div>
            </div>

            <!-- Avatar Usuario -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; background: #f97316; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span style="color: white; font-size: 12px; font-weight: 600;">CV</span>
                </div>
                <span style="font-size: 14px; font-weight: 500; color: #374151;">Carlos Ventas</span>
            </div>
        </div>
    </div>

    <div class="home-layout">
        <!-- Contenido Principal -->
        <div class="home-content">
            <div style="max-width: 100%; padding: 0 40px;">
                <h1 style="font-size: 2rem; font-weight: bold; color: #111827; margin-bottom: 16px;">
                    Página Home
                </h1>
                <p style="color: #6b7280; font-size: 1.125rem; margin-bottom: 32px;">
                    Esta es la nueva página Home con sidebar personalizado
                </p>

                <!-- Widget para Crear Post -->
                @livewire(\App\Filament\Widgets\CreatePostWidget::class)

                <!-- Widget de Social Posts -->
                @livewire(\App\Filament\Widgets\SocialPostWidget::class)
            </div>
        </div>

        <!-- Sidebar Derecho Verde -->
        <aside class="home-sidebar">
            <div style="height: 100%; overflow-y: auto;">
                @livewire(\App\Filament\Widgets\CalculadoraCorteWidget::class)
            </div>
        </aside>
    </div>
</div>