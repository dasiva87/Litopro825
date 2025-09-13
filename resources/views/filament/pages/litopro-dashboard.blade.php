<div class="litopro-dashboard-container">
    <style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* Force full layout override */
.fi-main {
    padding: 0 !important;
    height: 100vh !important;
    overflow: hidden !important;
}

.fi-page {
    padding: 0 !important;
    height: 100vh !important;
}

.fi-page-content {
    max-width: none !important;
    padding: 0 !important;
    height: 100vh !important;
}

/* Page wrapper styles */
.fi-page {
    height: 100vh !important;
    overflow: hidden !important;
}

/* Custom dashboard styles */
.litopro-dashboard * {
    font-family: 'Inter', sans-serif;
}

.sidebar-gradient {
    background: linear-gradient(145deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%);
}

.card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.stat-card {
    background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
}

.notifications-badge {
    animation: pulse 2s infinite;
}

.social-avatar {
    transition: all 0.3s ease;
}

.social-avatar:hover {
    transform: scale(1.1);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .dashboard-sidebar {
        position: fixed;
        left: -256px;
        top: 70px;
        height: calc(100vh - 70px);
        z-index: 40;
        transition: left 0.3s ease;
    }
    
    .dashboard-sidebar.open {
        left: 0;
    }
    
    .dashboard-main {
        width: 100%;
    }
    
    /* Hide some elements on mobile */
    .mobile-hide {
        display: none;
    }
    
    /* Adjust spacing on mobile */
    .topbar-mobile {
        padding: 0.5rem 1rem;
    }
}

/* Mobile menu button */
.mobile-menu-button {
    display: none;
}

@media (max-width: 1024px) {
    .mobile-menu-button {
        display: block;
    }
}
    </style>

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-6 py-3">
            <div class="flex items-center justify-between">
                <!-- Mobile menu button -->
                <button class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 mr-2" onclick="toggleMobileMenu()">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Logo LitoPro -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-xl font-bold text-gray-900">LitoPro</h1>
                        <p class="text-xs text-gray-500">Panel de Control</p>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="flex-1 max-w-lg mx-8">
                    <div class="relative">
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="Buscar cotizaciones, clientes, órdenes..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
                
                <!-- Right Side Actions -->
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Dashboard Button -->
                    <button class="hidden md:flex items-center space-x-2 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </button>
                    
                    <!-- Red Social Button -->
                    <button class="hidden md:flex items-center space-x-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                        </svg>
                        <span>Red Social</span>
                    </button>
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM21 12.3c-.6 0-1-.4-1-1V8.5c0-1.4-.6-2.8-1.6-3.7-1-.9-2.4-1.4-3.8-1.4s-2.8.5-3.8 1.4c-1 .9-1.6 2.3-1.6 3.7v2.8c0 .6-.4 1-1 1s-1-.4-1-1V8.5c0-2.1.8-4.1 2.3-5.6C10.9 1.4 12.9.6 15 .6s4.1.8 5.6 2.3c1.5 1.5 2.3 3.5 2.3 5.6v2.8c0 .6-.4 1-1 1z"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>
                    </div>
                    
                    <!-- User Avatar -->
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1)) }}
                            </span>
                        </div>
                        <span class="hidden sm:inline text-sm font-medium text-gray-900">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex h-screen pt-14">
        <!-- Sidebar -->
        <aside class="w-64 sidebar-gradient text-white flex-shrink-0">
            <div class="p-4 space-y-2">
                <!-- Navegación Principal -->
                <div class="space-y-1">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">COTIZACIONES & VENTAS</h3>
                    
                    <a href="{{ route('filament.admin.resources.documents.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md bg-blue-800 text-white">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Cotizaciones
                        <span class="ml-auto bg-orange-600 text-white text-xs rounded-full px-2 py-1">{{ $this->getActiveQuotations() }}</span>
                    </a>
                    
                    <a href="{{ route('filament.admin.resources.documents.create-quotation') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        Nueva Cotización
                    </a>
                    
                    <a href="{{ route('filament.admin.resources.contacts.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        Clientes
                        <span class="ml-auto bg-green-600 text-white text-xs rounded-full px-2 py-1">{{ $this->getActiveClients() }}</span>
                    </a>
                </div>
                
                <!-- Producción -->
                <div class="space-y-1 pt-4">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">PRODUCCIÓN</h3>
                    
                    <a href="{{ route('filament.admin.resources.documents.index', ['tableFilters[status][value]' => 'in_production']) }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Órdenes de Producción
                        <span class="ml-auto bg-yellow-600 text-white text-xs rounded-full px-2 py-1">{{ $this->getProductionOrders() }}</span>
                    </a>
                </div>
                
                <!-- Inventario & Papel -->
                <div class="space-y-1 pt-4">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">INVENTARIO</h3>
                    
                    <a href="{{ route('filament.admin.resources.papers.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Stock de Papel
                        @if($this->getCriticalStock() > 0)
                        <span class="ml-auto bg-red-600 text-white text-xs rounded-full px-2 py-1">!</span>
                        @endif
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <div class="p-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Cotizaciones Activas -->
                    <div class="stat-card p-6 rounded-xl shadow-sm card-hover text-white" style="--bg-from: #3b82f6; --bg-to: #1d4ed8;">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-blue-100">Cotizaciones Activas</dt>
                                    <dd class="text-2xl font-semibold text-white">{{ $this->getActiveQuotations() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Órdenes en Producción -->
                    <div class="stat-card p-6 rounded-xl shadow-sm card-hover text-white" style="--bg-from: #f59e0b; --bg-to: #d97706;">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-orange-100">Producción</dt>
                                    <dd class="text-2xl font-semibold text-white">{{ $this->getProductionOrders() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Ingresos del Mes -->
                    <div class="stat-card p-6 rounded-xl shadow-sm card-hover text-white" style="--bg-from: #10b981; --bg-to: #059669;">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-green-100">Ingresos del Mes</dt>
                                    <dd class="text-2xl font-semibold text-white">${{ number_format($this->getMonthlyRevenue(), 1) }}M</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Clientes Activos -->
                    <div class="stat-card p-6 rounded-xl shadow-sm card-hover text-white" style="--bg-from: #8b5cf6; --bg-to: #7c3aed;">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-purple-100">Clientes Activos</dt>
                                    <dd class="text-2xl font-semibold text-white">{{ $this->getActiveClients() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🚀 Acciones Rápidas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <a href="{{ route('filament.admin.resources.documents.create-quotation') }}" 
                           class="bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 text-center transition-colors">
                            <div class="text-blue-600 text-2xl mb-2">📝</div>
                            <div class="text-sm font-medium text-blue-900">Nueva Cotización</div>
                        </a>
                        <a href="{{ route('filament.admin.resources.contacts.create') }}" 
                           class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 text-center transition-colors">
                            <div class="text-green-600 text-2xl mb-2">👤</div>
                            <div class="text-sm font-medium text-green-900">Nuevo Cliente</div>
                        </a>
                        <a href="{{ route('filament.admin.resources.products.create') }}" 
                           class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 text-center transition-colors">
                            <div class="text-purple-600 text-2xl mb-2">📦</div>
                            <div class="text-sm font-medium text-purple-900">Nuevo Producto</div>
                        </a>
                        <a href="{{ route('filament.admin.resources.magazine-items.create') }}" 
                           class="bg-pink-50 hover:bg-pink-100 border border-pink-200 rounded-lg p-4 text-center transition-colors">
                            <div class="text-pink-600 text-2xl mb-2">📖</div>
                            <div class="text-sm font-medium text-pink-900">Nueva Publicación</div>
                        </a>
                        <a href="{{ route('filament.admin.pages.cutting-calculator') }}" 
                           class="bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg p-4 text-center transition-colors">
                            <div class="text-orange-600 text-2xl mb-2">🧮</div>
                            <div class="text-sm font-medium text-orange-900">Calculadora</div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
// Dashboard initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('LitoPro Dashboard loaded successfully');
});

// Toggle mobile menu
function toggleMobileMenu() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}
    </script>
</div>