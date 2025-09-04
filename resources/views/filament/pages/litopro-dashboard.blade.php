<x-filament-panels::page>
<div class="litopro-dashboard-wrapper">
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

/* Wrapper styles */
.litopro-dashboard-wrapper {
    height: 100vh;
    overflow: hidden;
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
        top: 80px;
        height: calc(100vh - 80px);
        z-index: 40;
        transition: left 0.3s ease;
    }
    
    .dashboard-sidebar.open {
        left: 0;
    }
    
    .dashboard-main {
        width: 100%;
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
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Mobile menu button -->
            <button class="mobile-menu-button p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 mr-2" onclick="toggleMobileMenu()">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            
            <!-- Logo y Título -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">L</span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">LitoPro</h1>
                    <span class="text-sm text-gray-500">Panel de Control - Litografía</span>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="flex-1 max-w-lg mx-8">
                <div class="relative">
                    <input type="text" placeholder="Buscar cotizaciones, clientes, órdenes..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            
            <!-- Notifications y User -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5zm0 0V9a6 6 0 00-12 0v8h12z"/>
                        </svg>
                        <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full notifications-badge"></span>
                    </button>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->company->name }}</p>
                    </div>
                    <img class="h-8 w-8 rounded-full" 
                         src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3b82f6&color=fff" 
                         alt="User">
                </div>
                
                <!-- Alert Banner -->
                @if($this->getCriticalStock() > 0)
                <div class="bg-orange-100 border border-orange-200 rounded-lg px-3 py-2">
                    <div class="flex items-center space-x-2">
                        <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-sm font-medium text-orange-800">
                            Tienes {{ $this->getCriticalStock() }} tipos de papel con stock crítico
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</header>

<div class="flex h-screen pt-16">
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
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Plantillas
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
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4M3 21h18l-2-9H5l-2 9z"/>
                        </svg>
                        Estado Producción
                    </a>
                    
                    <a href="{{ route('filament.admin.resources.printing-machines.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        Máquinas
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        Acabados
                    </a>
                </div>
                
                <!-- Inventario & Papel -->
                <div class="space-y-1 pt-4">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">INVENTARIO & PAPEL</h3>
                    
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
                    
                    <a href="{{ route('filament.admin.resources.products.index') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Inventario
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Pedidos de Papel
                        <span class="ml-auto bg-blue-600 text-white text-xs rounded-full px-2 py-1">5</span>
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Marketplace
                    </a>
                    
                    <a href="{{ route('filament.admin.resources.contacts.index', ['tableFilters[type][value]' => 'supplier']) }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Proveedores
                    </a>
                </div>
                
                <!-- Herramientas -->
                <div class="space-y-1 pt-4">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">HERRAMIENTAS</h3>
                    
                    <a href="{{ route('filament.admin.pages.cutting-calculator') }}" 
                       class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Calculadoras
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Reportes
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        Analytics
                    </a>
                </div>
                
                <!-- Red Social -->
                <div class="space-y-1 pt-4">
                    <h3 class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-3">RED SOCIAL</h3>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                        </svg>
                        Explorar
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                        Conexiones
                    </a>
                    
                    <a href="#" class="flex items-center px-3 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-800 hover:text-white transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        Mensajes
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
                                    <dd class="text-xs text-blue-100">Estado: Enviadas y En Revisión</dd>
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
                                    <dt class="text-sm font-medium text-orange-100">Órdenes en Producción</dt>
                                    <dd class="text-2xl font-semibold text-white">{{ $this->getProductionOrders() }}</dd>
                                    <dd class="text-xs text-orange-100">En proceso y programadas</dd>
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
                                    <dd class="text-xs text-green-100">Documentos facturados</dd>
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
                                    <dd class="text-xs text-purple-100">Con cotizaciones recientes</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                    <!-- Main Content Column -->
                    <div class="xl:col-span-3 space-y-6">
                        <!-- Acciones Rápidas Widget -->
                        @livewire(\App\Filament\Widgets\QuickActionsWidget::class, [], key('quick-actions'))

                        <!-- Actividad Reciente -->
                        <div class="bg-white rounded-xl shadow-sm">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">📋 Actividad Reciente</h3>
                            </div>
                            <div class="divide-y divide-gray-200">
                                @foreach($this->getRecentActivity() as $activity)
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-2 h-2 rounded-full
                                            @if($activity['status_color'] === 'gray') bg-gray-600
                                            @elseif($activity['status_color'] === 'yellow') bg-yellow-600
                                            @elseif($activity['status_color'] === 'green') bg-green-600
                                            @elseif($activity['status_color'] === 'orange') bg-orange-600
                                            @elseif($activity['status_color'] === 'blue') bg-blue-600
                                            @elseif($activity['status_color'] === 'red') bg-red-600
                                            @else bg-gray-600
                                            @endif">
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900">{{ $activity['id'] }}</p>
                                                <span class="text-xs text-gray-500">{{ $activity['time_diff'] }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600">{{ $activity['title'] }}</p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <span class="text-sm text-gray-500">{{ $activity['client'] }}</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($activity['status_color'] === 'gray') bg-gray-100 text-gray-800
                                                    @elseif($activity['status_color'] === 'yellow') bg-yellow-100 text-yellow-800
                                                    @elseif($activity['status_color'] === 'green') bg-green-100 text-green-800
                                                    @elseif($activity['status_color'] === 'orange') bg-orange-100 text-orange-800
                                                    @elseif($activity['status_color'] === 'blue') bg-blue-100 text-blue-800
                                                    @elseif($activity['status_color'] === 'red') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ $activity['status_label'] }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900">${{ number_format($activity['total']) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Compartir en la Red Social -->
                        <div class="bg-white p-6 rounded-xl shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">💬 Compartir en la Red Social</h3>
                            <div class="space-y-4">
                                <textarea rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                          placeholder="¿Qué quieres compartir con la comunidad de LitoPro? Promociones, trabajos terminados, consejos técnicos..."></textarea>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <button class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-sm">Imagen</span>
                                        </button>
                                        <button class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 00-2.828-2.828l-6.586 6.586a2 2 0 11-2.828-2.828L13.343 4.929a4 4 0 116.586 6.586L13.343 18.1a4 4 0 01-6.586-6.586z"/>
                                            </svg>
                                            <span class="text-sm">Archivo</span>
                                        </button>
                                    </div>
                                    <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                        Publicar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Social Feed Widget -->
                        @livewire(\App\Filament\Widgets\SocialFeedWidget::class, [], key('social-feed'))
                    </div>

                    <!-- Sidebar Column -->
                    <div class="space-y-6">
                        <!-- Stock Alerts Widget -->
                        @livewire(\App\Filament\Widgets\StockAlertsWidget::class, [], key('stock-alerts'))

                        <!-- Ofertas en Marketplace -->
                        <div class="bg-white p-6 rounded-xl shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">🛒 Ofertas en Marketplace</h3>
                                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="space-y-3">
                                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Bond 75g</p>
                                            <p class="text-xs text-green-700">Disponible ahora</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-green-900">$2,300</p>
                                            <p class="text-xs text-green-700">por pliego</p>
                                        </div>
                                    </div>
                                    <button class="mt-2 w-full bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">
                                        Ver Oferta
                                    </button>
                                </div>
                                
                                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Couche 150g</p>
                                            <p class="text-xs text-green-700">Stock limitado</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-green-900">$4,100</p>
                                            <p class="text-xs text-green-700">por pliego</p>
                                        </div>
                                    </div>
                                    <button class="mt-2 w-full bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1.5 px-3 rounded transition-colors">
                                        Ver Oferta
                                    </button>
                                </div>
                                
                                <button class="w-full text-center py-2 text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">
                                    Ver Marketplace Completo
                                </button>
                            </div>
                        </div>

                        <!-- Paper Calculator Widget -->
                        @livewire(\App\Filament\Widgets\PaperCalculatorWidget::class, [], key('paper-calculator'))

                        <!-- Deadlines Widget -->
                        @livewire(\App\Filament\Widgets\DeadlinesWidget::class, [], key('deadlines'))
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Inicialización del dashboard personalizado
document.addEventListener('DOMContentLoaded', function() {
    console.log('LitoPro Dashboard cargado correctamente');
    
    // Auto-refresh de widgets cada 5 minutos
    setInterval(function() {
        if (typeof Livewire !== 'undefined') {
            Livewire.emit('refreshWidgets');
        }
    }, 300000);
});

// Toggle mobile menu
function toggleMobileMenu() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    sidebar.classList.toggle('open');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const menuButton = document.querySelector('.mobile-menu-button');
    
    if (!sidebar.contains(event.target) && !menuButton.contains(event.target)) {
        sidebar.classList.remove('open');
    }
});
</script>
</div>
</x-filament-panels::page>