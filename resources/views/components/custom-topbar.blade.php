<style>
    /* Ocultar topbar original de Filament */
    .fi-topbar {
        display: none !important;
    }

    /* Layout principal ajustado */
    .fi-main {
        position: relative !important;
        margin-top: 64px !important;
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

    /* Dark theme styles */
    .dark .custom-topbar {
        background: #1f2937 !important;
        border-bottom-color: #374151 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .custom-topbar {
            padding: 8px 16px !important;
        }

        .search-container {
            display: none !important;
        }

        .nav-buttons {
            gap: 8px !important;
        }
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
            <div style="font-size: 18px; font-weight: 700; color: #111827; line-height: 1;" class="dark:text-white">LitoPro</div>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-container" style="flex: 1; max-width: 600px; margin: 0 32px;">
        <div style="position: relative;">
            <svg style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                placeholder="Buscar cotizaciones, clientes, órdenes..."
                style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid #d1d5db; border-radius: 24px; font-size: 14px; background: #f9fafb; color: #374151;"
                class="dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            >
        </div>
    </div>

    <!-- Botones de navegación y usuario -->
    <div class="nav-buttons" style="display: flex; align-items: center; gap: 16px;">
        <!-- Dashboard Button -->
        <a href="{{ url('/admin/home') }}"
           style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: {{ request()->is('admin/home') ? '#eff6ff' : '#f3f4f6' }}; color: {{ request()->is('admin/home') ? '#3b82f6' : '#6b7280' }}; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; cursor: pointer;"
           class="hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('admin/home') ? 'dark:bg-blue-900 dark:text-blue-300' : 'dark:bg-gray-700 dark:text-gray-300' }}"
        >
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        <!-- Red Social Button -->
        <button style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f3f4f6; color: #6b7280; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;"
                class="hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
        >
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
            </svg>
            Red Social
        </button>

        <!-- Notificaciones -->
        @if(auth()->check())
            @livewire(\App\Filament\Widgets\NotificationDropdownWidget::class)
        @endif

        <!-- Avatar Usuario con Dropdown -->
        @if(auth()->check())
            <div style="position: relative;" x-data="{ userDropdown: false }">
                <button
                    @click="userDropdown = !userDropdown"
                    style="display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; padding: 6px 8px; border-radius: 8px; transition: background-color 0.2s;"
                    class="hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <div style="width: 36px; height: 36px; background: #f97316; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span style="color: white; font-size: 14px; font-weight: 600; line-height: 1;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    </div>
                    <span style="font-size: 14px; font-weight: 500; color: #374151;" class="hidden md:inline dark:text-white">{{ auth()->user()->name }}</span>
                   
                </button>

                <!-- Dropdown Menu -->
                <div
                    x-show="userDropdown"
                    x-transition
                    @click.away="userDropdown = false"
                    style="position: absolute; right: 0; top: 100%; width: 200px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 50; margin-top: 8px;"
                    class="dark:bg-gray-800 dark:border-gray-600"
                >
                    <!-- User Info Header -->
                    <div style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;" class="dark:border-gray-600">
                        <div style="font-size: 14px; font-weight: 500; color: #111827;" class="dark:text-white">{{ auth()->user()->name }}</div>
                        <div style="font-size: 12px; color: #6b7280;" class="dark:text-gray-400">{{ auth()->user()->email }}</div>
                    </div>

                    <!-- Menu Items -->
                    <div style="padding: 8px 0;">
                        <!-- Theme Toggle -->
                        <button
                            onclick="toggleTheme()"
                            style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 8px 16px; background: none; border: none; cursor: pointer; font-size: 14px; color: #374151; transition: background-color 0.2s;"
                            class="hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <span>Tema Oscuro</span>
                        </button>

                        <!-- Divider -->
                        <div style="height: 1px; background: #f3f4f6; margin: 4px 0;" class="dark:bg-gray-600"></div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('filament.admin.auth.logout') }}" style="margin: 0;">
                            @csrf
                            <button
                                type="submit"
                                style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 8px 16px; background: none; border: none; cursor: pointer; font-size: 14px; color: #dc2626; transition: background-color 0.2s;"
                                class="hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/50"
                            >
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Theme Toggle JavaScript -->
<script>
    function toggleTheme() {
        const body = document.body;
        const html = document.documentElement;

        if (body.classList.contains('dark')) {
            // Switch to light mode
            body.classList.remove('dark');
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');

            // Update button text
            updateThemeButtonText('Tema Oscuro');
        } else {
            // Switch to dark mode
            body.classList.add('dark');
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');

            // Update button text
            updateThemeButtonText('Tema Claro');
        }
    }

    function updateThemeButtonText(text) {
        const themeButton = document.querySelector('[onclick="toggleTheme()"] span');
        if (themeButton) {
            themeButton.textContent = text;
        }
    }

    // Initialize theme on page load
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme');
        const body = document.body;
        const html = document.documentElement;

        if (savedTheme === 'dark') {
            body.classList.add('dark');
            html.classList.add('dark');
            updateThemeButtonText('Tema Claro');
        } else {
            updateThemeButtonText('Tema Oscuro');
        }
    });
</script>