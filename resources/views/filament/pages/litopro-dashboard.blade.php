<x-filament-panels::page>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        /* LitoPro Dashboard Custom Styles with high specificity */
        .fi-page .litopro-dashboard,
        .fi-page .litopro-dashboard * {
            font-family: 'Inter', sans-serif !important;
        }

        .fi-page .litopro-dashboard {
            background-color: #f9fafb !important;
            min-height: 100vh !important;
        }

        /* Card hover effects */
        .fi-page .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .fi-page .card-hover:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        /* Navigation tabs */
        .fi-page .nav-tab {
            transition: all 0.2s ease-in-out !important;
        }

        .fi-page .nav-tab-active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            border-color: #bfdbfe !important;
        }

        .fi-page .nav-tab:hover {
            background-color: #f9fafb !important;
        }

        /* Override Filament panel styles */
        .fi-main {
            background-color: transparent !important;
        }

        .fi-page {
            background-color: transparent !important;
        }

        .fi-page-content {
            background-color: transparent !important;
            padding: 0 !important;
        }

        /* Stats cards */
        .fi-page .stats-card {
            background: white !important;
            padding: 1.5rem !important;
            border-radius: 1rem !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
            border: 1px solid #f3f4f6 !important;
            overflow: hidden !important;
        }

        .fi-page .stats-card h3 {
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #6b7280 !important;
            margin-bottom: 0.25rem !important;
        }

        .fi-page .stats-card p.stats-value {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
        }

        .fi-page .stats-card p.stats-label {
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            margin-top: 0.25rem !important;
        }

        .fi-page .icon-gradient {
            width: 3.5rem !important;
            height: 3.5rem !important;
            border-radius: 1rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }

        /* Header styles */
        .fi-page .header-nav {
            background: white !important;
            border-bottom: 1px solid #e5e7eb !important;
            padding: 1.5rem !important;
        }

        .fi-page .header-nav h1 {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
        }

        .fi-page .header-nav p {
            font-size: 0.875rem !important;
            color: #6b7280 !important;
            margin-top: 0.25rem !important;
        }

        /* Tab navigation */
        .fi-page .tab-nav {
            background: #f3f4f6 !important;
            border-radius: 0.5rem !important;
            padding: 0.25rem !important;
        }

        .fi-page .tab-nav button {
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            transition: all 0.2s ease-in-out !important;
        }

        /* Grid responsiveness */
        .fi-page .dashboard-grid {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            gap: 1.5rem !important;
            margin-bottom: 2rem !important;
        }

        @media (min-width: 768px) {
            .fi-page .dashboard-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        @media (min-width: 1024px) {
            .fi-page .dashboard-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            }
        }

        /* Widget spacing */
        .fi-page .widget-container {
            margin-bottom: 2rem !important;
        }

        .fi-page .widget-container:last-child {
            margin-bottom: 0 !important;
        }

        /* Social Section Specific Styles */
        .fi-page .social-section {
            animation: fadeIn 0.6s ease-out !important;
            background-color: transparent !important;
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
            padding: 1.5rem !important;
            border-radius: 1rem !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
            background: white !important;
        }

        .fi-page .social-section article {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid #f3f4f6 !important;
            margin-bottom: 1.5rem !important;
            background-color: white !important;
        }

        .fi-page .social-section article:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            transform: translateY(-2px) !important;
        }

        /* Social Post Enhancements */
        .fi-page .social-post-avatar {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
            transition: transform 0.2s ease !important;
        }

        .fi-page .social-post-avatar:hover {
            transform: scale(1.05) !important;
        }

        /* Social Buttons */
        .fi-page .social-action-btn {
            transition: all 0.2s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .fi-page .social-action-btn:hover {
            transform: translateY(-1px) !important;
        }

        .fi-page .social-action-btn:active {
            transform: translateY(0) !important;
        }

        /* Special Post Types */
        .fi-page .stock-alert-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            border-left: 4px solid #f59e0b !important;
        }

        .fi-page .work-completed-badge {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
            color: #065f46 !important;
            font-weight: 600 !important;
        }

        /* Chat Status Indicators */
        .fi-page .chat-status-online {
            background-color: #10b981 !important;
            box-shadow: 0 0 0 2px #ffffff, 0 0 8px rgba(16, 185, 129, 0.4) !important;
        }

        .fi-page .chat-status-away {
            background-color: #f59e0b !important;
            box-shadow: 0 0 0 2px #ffffff, 0 0 8px rgba(245, 158, 11, 0.4) !important;
        }

        .fi-page .chat-status-offline {
            background-color: #6b7280 !important;
            box-shadow: 0 0 0 2px #ffffff !important;
        }

        /* Trending Tags */
        .fi-page .trending-tag {
            transition: all 0.2s ease !important;
            border-radius: 0.5rem !important;
        }

        .fi-page .trending-tag:hover {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            cursor: pointer !important;
        }

        /* Post Images Enhancement */
        .fi-page .post-image-container {
            position: relative !important;
            overflow: hidden !important;
            border-radius: 0.75rem !important;
        }

        .fi-page .post-image-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: linear-gradient(45deg, rgba(0,0,0,0.1), rgba(255,255,255,0.1)) !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
        }

        .fi-page .post-image-container:hover .post-image-overlay {
            opacity: 1 !important;
        }

        /* Enhanced Comments */
        .fi-page .comment-bubble {
            position: relative !important;
            background: #f9fafb !important;
            border: 1px solid #e5e7eb !important;
            transition: all 0.2s ease !important;
        }

        .fi-page .comment-bubble:hover {
            background: #f3f4f6 !important;
            border-color: #d1d5db !important;
        }

        /* Social Feed Loading Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Pulse Animation for Active Elements */
        .fi-page .pulse-animation {
            animation: pulse 2s infinite !important;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Enhanced Social Header */
        .fi-page .social-header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }

        .fi-page .social-stats-counter {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
    </style>

    <div class="litopro-dashboard bg-gray-50 min-h-screen">
        <!-- Header con navegación -->
        <div class="header-nav">
            <div class="flex items-center justify-between">
                <div>
                    <h1>{{ $this->getTitle() }}</h1>
                    <p>{{ $this->getSubheading() }}</p>
                </div>
                
                <!-- Navigation Tabs -->
                <div class="tab-nav flex space-x-1">
                    <button onclick="scrollToSection('dashboard')" id="dashboardTab" class="nav-tab nav-tab-active">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Dashboard
                    </button>
                    <button onclick="scrollToSection('social')" id="socialTab" class="nav-tab text-gray-600">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                        </svg>
                        Red Social
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div id="dashboard" class="p-6">
        
            <!-- Stats Cards Modernos -->
            <div class="dashboard-grid widget-container">
                <!-- Cotizaciones -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-blue-500 to-blue-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Cotizaciones</h3>
                            <p class="stats-value">{{ $this->getActiveQuotations() }}</p>
                            <p class="stats-label text-green-600">↗ Activas</p>
                        </div>
                    </div>
                </div>
                
                <!-- Órdenes -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-orange-500 to-orange-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Producción</h3>
                            <p class="stats-value">{{ $this->getProductionOrders() }}</p>
                            <p class="stats-label text-orange-600">⏳ En proceso</p>
                        </div>
                    </div>
                </div>
                
                <!-- Ingresos -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-green-500 to-green-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Ingresos</h3>
                            <p class="stats-value">${{ number_format($this->getMonthlyRevenue() * 1000000, 0) }}</p>
                            <p class="stats-label text-green-600">📈 Este mes</p>
                        </div>
                    </div>
                </div>
                
                <!-- Clientes -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-purple-500 to-purple-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Clientes</h3>
                            <p class="stats-value">{{ $this->getActiveClients() }}</p>
                            <p class="stats-label text-purple-600">👥 Activos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Crítico -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-red-500 to-red-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Stock Crítico</h3>
                            <p class="stats-value">{{ $this->getCriticalStock() }}</p>
                            <p class="stats-label text-red-600">⚠️ Productos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Actividad Reciente -->
                <div class="stats-card card-hover">
                    <div class="flex items-center space-x-4">
                        <div class="icon-gradient bg-gradient-to-r from-indigo-500 to-indigo-600">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Actividad</h3>
                            <p class="stats-value">{{ $this->getRecentActivity()->count() }}</p>
                            <p class="stats-label text-indigo-600">⚡ Recientes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="widget-container">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <!-- Left Column - Documents Table -->
                    <div class="xl:col-span-2">
                        <div class="stats-card">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Documentos Activos
                                </h2>
                            </div>
                            <div class="p-0">
                                @livewire(\App\Filament\Widgets\ActiveDocumentsWidget::class)
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Widgets Sidebar -->
                    <div class="space-y-6">
                        <!-- Stock Alerts -->
                        <div class="widget-container">
                            @livewire(\App\Filament\Widgets\StockAlertsWidget::class)
                        </div>
                        
                        <!-- Paper Calculator -->
                        <div class="widget-container">
                            @livewire(\App\Filament\Widgets\PaperCalculatorWidget::class)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Separador visual -->
            <div class="relative my-12">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <div class="bg-gray-50 px-6 py-2 rounded-full">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN RED SOCIAL -->
        <div id="social" class="bg-gradient-to-b from-gray-50 to-white rounded-t-3xl shadow-lg border-t border-gray-200">
            <div class="p-6">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Red Social LitoPro</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">Conecta con empresas del sector gráfico en Colombia. Comparte proyectos, encuentra proveedores y mantente al día con las tendencias del mercado.</p>
                </div>
                
                @livewire(\App\Filament\Widgets\SocialFeedWidget::class)
            </div>
        </div>
    </div>

    <!-- JavaScript para navegación -->
    <script>
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
                
                // Update tab states
                const dashboardTab = document.getElementById('dashboardTab');
                const socialTab = document.getElementById('socialTab');
                
                if (sectionId === 'dashboard') {
                    dashboardTab.classList.add('nav-tab-active');
                    dashboardTab.classList.remove('text-gray-600');
                    socialTab.classList.remove('nav-tab-active');
                    socialTab.classList.add('text-gray-600');
                } else {
                    socialTab.classList.add('nav-tab-active');
                    socialTab.classList.remove('text-gray-600');
                    dashboardTab.classList.remove('nav-tab-active');
                    dashboardTab.classList.add('text-gray-600');
                }
            }
        }

        // Optimized scroll handling with debounce and RAF
        let scrollTimeout;
        let isScrolling = false;

        function handleScroll() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    const dashboardSection = document.getElementById('dashboard');
                    const socialSection = document.getElementById('social');
                    const scrollPosition = window.scrollY + window.innerHeight / 3;
                    
                    if (dashboardSection && socialSection) {
                        const dashboardTop = dashboardSection.offsetTop;
                        const socialTop = socialSection.offsetTop;
                        
                        const dashboardTab = document.getElementById('dashboardTab');
                        const socialTab = document.getElementById('socialTab');
                        
                        if (scrollPosition >= socialTop) {
                            // Update to social tab
                            if (!socialTab.classList.contains('nav-tab-active')) {
                                dashboardTab.classList.remove('nav-tab-active');
                                dashboardTab.classList.add('text-gray-600');
                                socialTab.classList.add('nav-tab-active');
                                socialTab.classList.remove('text-gray-600');
                            }
                        } else if (scrollPosition >= dashboardTop - 100) {
                            // Update to dashboard tab
                            if (!dashboardTab.classList.contains('nav-tab-active')) {
                                socialTab.classList.remove('nav-tab-active'); 
                                socialTab.classList.add('text-gray-600');
                                dashboardTab.classList.add('nav-tab-active');
                                dashboardTab.classList.remove('text-gray-600');
                            }
                        }
                    }
                    isScrolling = false;
                });
            }
            isScrolling = true;
        }

        // Debounced scroll listener
        document.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(handleScroll, 10);
        }, { passive: true });
    </script>
</x-filament-panels::page>