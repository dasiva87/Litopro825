<div class="social-section" wire:poll.keep-alive.30s="updateFeedData" 
     x-data="{
    posts: @js($posts ?? []),
    postTypes: @js($postTypes ?? []),
    suggestedCompanies: @js($suggestedCompanies ?? []),
    trends: @js($trends ?? []),
    chatContacts: @js($chatContacts ?? []),
    notifications: @js($notifications ?? []),
    currentUser: @js($currentUser ?? []),
    newPostContent: @entangle('newPostContent'),
    newPostType: @entangle('newPostType'),
    searchQuery: @entangle('searchQuery').live,
    selectedFilter: @entangle('selectedFilter'),
    isOnline: true,
    lastUpdate: new Date().toLocaleTimeString(),
    connectionStatus: 'Conectado',
    
    init() {
        this.setupEventListeners();
        this.monitorConnection();
    },
    
    setupEventListeners() {
        Livewire.on('feed-updated', () => {
            this.lastUpdate = new Date().toLocaleTimeString();
            this.connectionStatus = 'Actualizado: ' + this.lastUpdate;
            console.log('Feed updated at:', this.lastUpdate);
        });
        
        Livewire.on('post-created', () => {
            this.newPostContent = '';
            this.$nextTick(() => {
                const postsContainer = this.$el.querySelector('.social-posts');
                if (postsContainer) {
                    postsContainer.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
        
        Livewire.on('posts-loaded', () => {
            this.connectionStatus = 'Nuevos posts cargados';
            setTimeout(() => {
                this.connectionStatus = 'Conectado';
            }, 3000);
        });
    },
    
    monitorConnection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.connectionStatus = 'Conexión restaurada';
            console.log('Connection restored - resuming updates');
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.connectionStatus = 'Sin conexión';
            console.log('Connection lost - updates paused');
        });
    },
    
    togglePostMenu(postId) {
        const menu = document.getElementById(`postMenu${postId}`);
        if (menu) {
            menu.classList.toggle('hidden');
        }
    },
    
    publishPost() {
        if (this.newPostContent.trim()) {
            $wire.createPost();
        } else {
            this.$dispatch('show-alert', { message: 'Por favor escribe algo antes de publicar', type: 'warning' });
        }
    }
}" class="social-section"
    :class="{ 'opacity-75': !isOnline }">
    <!-- Header Mejorado de la Sección -->
    <div class="mb-8">
        <div class="social-header-gradient rounded-xl shadow-lg border border-gray-200 p-6 relative overflow-hidden">
            <!-- Efecto de partículas de fondo -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-4 left-8 w-2 h-2 bg-white rounded-full pulse-animation"></div>
                <div class="absolute top-12 right-16 w-3 h-3 bg-white rounded-full pulse-animation" style="animation-delay: 0.5s;"></div>
                <div class="absolute bottom-8 left-1/4 w-2 h-2 bg-white rounded-full pulse-animation" style="animation-delay: 1s;"></div>
                <div class="absolute bottom-4 right-8 w-1 h-1 bg-white rounded-full pulse-animation" style="animation-delay: 1.5s;"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-5 h-5 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                            </svg>
                            Red Social LitoPro
                        </h1>
                        <p class="text-white text-opacity-90 mt-2 text-lg">🚀 Conectando el gremio de artes gráficas en Colombia</p>
                        <div class="flex items-center mt-3 space-x-6">
                            <div class="social-stats-counter px-3 py-1 rounded-full">
                                <span class="text-white font-semibold">156 empresas</span>
                            </div>
                            <div class="social-stats-counter px-3 py-1 rounded-full">
                                <span class="text-white font-semibold">2.4k publicaciones</span>
                            </div>
                            <div class="social-stats-counter px-3 py-1 rounded-full">
                                <span class="text-white font-semibold">47 ciudades</span>
                            </div>
                            <!-- Live indicator with connection status -->
                            <div class="social-stats-counter px-3 py-1 rounded-full flex items-center">
                                <div class="w-2 h-2 rounded-full mr-2 pulse-animation" 
                                     :class="isOnline ? 'bg-green-400' : 'bg-red-400'"></div>
                                <span class="text-white font-semibold text-sm" x-text="isOnline ? '🟢 En línea' : '🔴 Sin conexión'"></span>
                                
                                <!-- Polling indicators -->
                                <svg wire:loading wire:target="updateFeedData" class="animate-spin w-3 h-3 ml-2 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                
                                <svg wire:loading wire:target="refreshFeed" class="animate-spin w-3 h-3 ml-2 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            
                            <!-- Connection status text -->
                            <div class="social-stats-counter px-3 py-1 rounded-full">
                                <span class="text-white font-semibold text-xs" x-text="connectionStatus"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex -space-x-3">
                            <div class="w-12 h-12 bg-blue-500 rounded-full border-3 border-white flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-bold">LG</span>
                            </div>
                            <div class="w-12 h-12 bg-green-500 rounded-full border-3 border-white flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-bold">PC</span>
                            </div>
                            <div class="w-12 h-12 bg-purple-500 rounded-full border-3 border-white flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-bold">TD</span>
                            </div>
                            <div class="w-12 h-12 bg-orange-500 rounded-full border-3 border-white flex items-center justify-center shadow-lg">
                                <span class="text-white text-sm font-bold">+12</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <button wire:click="refreshFeed" class="flex flex-col items-center hover:bg-white hover:bg-opacity-10 rounded-lg p-2 transition-colors">
                                <span class="text-white text-lg font-semibold block">🔄</span>
                                <span class="text-white text-xs">Actualizar</span>
                            </button>
                            <!-- Loading indicator for refresh -->
                            <div wire:loading wire:target="refreshFeed" class="text-white text-xs">
                                <svg class="animate-spin w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de búsqueda y filtros mejorada -->
    <div class="mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input wire:model.live="searchQuery" 
                           type="text" 
                           placeholder="Buscar publicaciones, empresas, productos..." 
                           class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    
                    <!-- Loading indicator for search -->
                    <div wire:loading wire:target="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Filtros rápidos -->
                <div class="flex space-x-2">
                    <select wire:model="selectedFilter" class="border border-gray-300 rounded-lg px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="all">Todos</option>
                        <option value="posts">Publicaciones</option>
                        <option value="offers">Ofertas</option>
                        <option value="companies">Empresas</option>
                    </select>
                    
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"/>
                        </svg>
                        Filtros
                    </button>
                </div>
            </div>
            
            <!-- Search results hint -->
            <div x-show="$wire.searchQuery.length > 0" class="mt-3 text-sm text-gray-600">
                <span>Buscando: "</span>
                <span class="font-semibold text-blue-600" x-text="$wire.searchQuery"></span>
                <span>"</span>
                <span wire:loading wire:target="searchQuery" class="text-blue-500">...</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Feed Principal (Columna Izquierda y Central) -->
        <div class="lg:col-span-2 space-y-6 social-posts">

            <!-- Formulario para Publicar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0 social-post-avatar">
                            <span class="text-white font-semibold" x-text="currentUser.avatar_initials || 'CV'"></span>
                        </div>
                        <div class="flex-1">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <textarea 
                                    x-model="newPostContent"
                                    placeholder="¿Qué quieres compartir con la comunidad de LitoPro? Promociones, trabajos terminados, consejos técnicos..." 
                                    class="w-full bg-transparent border-none resize-none placeholder-gray-500 text-gray-900 focus:outline-none"
                                    rows="3"></textarea>
                            </div>
                            
                            <!-- Opciones de Contenido -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <button class="flex items-center px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm">Imagen</span>
                                    </button>
                                    <button class="flex items-center px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 00-2.828-2.828l-6.586 6.586a2 2 0 11-2.828-2.828L13.343 4.929a4 4 0 116.586 6.586L13.343 18.1a4 4 0 01-6.586-6.586z"/>
                                        </svg>
                                        <span class="text-sm">Archivo</span>
                                    </button>
                                    <button class="flex items-center px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="text-sm">Encuesta</span>
                                    </button>
                                    <button class="flex items-center px-3 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="text-sm">Ubicación</span>
                                    </button>
                                </div>
                                
                                <!-- Configuración de Privacidad -->
                                <div class="flex items-center space-x-3">
                                    <select x-model="newPostType" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500">
                                        <template x-for="(label, value) in postTypes" :key="value">
                                            <option :value="value" x-text="label"></option>
                                        </template>
                                    </select>
                                    
                                    <button 
                                        wire:click="createPost"
                                        wire:loading.attr="disabled"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-semibold px-6 py-2 rounded-lg transition-colors flex items-center">
                                        
                                        <!-- Normal state icon -->
                                        <svg wire:loading.remove wire:target="createPost" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        
                                        <!-- Loading state spinner -->
                                        <svg wire:loading wire:target="createPost" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        
                                        <span wire:loading.remove wire:target="createPost">Publicar</span>
                                        <span wire:loading wire:target="createPost">Publicando...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts del Feed -->
            <template x-for="post in posts" :key="post.id">
                <article class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6">
                        <!-- Header del Post -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div :class="`w-12 h-12 ${post.avatar_bg} rounded-full flex items-center justify-center social-post-avatar`">
                                    <span class="text-white font-semibold text-sm" x-text="post.avatar_initials"></span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900" x-text="post.company_name"></h3>
                                    <div class="flex items-center text-sm text-gray-500 space-x-2">
                                        <span x-text="post.time"></span>
                                        <span>•</span>
                                        <div class="flex items-center">
                                            <i :class="post.visibility_icon" class="mr-1 text-xs"></i>
                                            <span x-text="post.visibility"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative">
                                <button class="text-gray-400 hover:text-gray-600 p-2" @click="togglePostMenu(post.id)">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/>
                                    </svg>
                                </button>
                                <div :id="`postMenu${post.id}`" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                        </svg>
                                        Guardar post
                                    </a>
                                    <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                        </svg>
                                        Reportar
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Contenido del Post -->
                        <div class="mb-4">
                            <!-- Contenido Normal -->
                            <template x-if="!post.special_type">
                                <p class="text-gray-900 mb-3" x-text="post.content"></p>
                            </template>
                            
                            <!-- Alerta de Stock Mejorada -->
                            <template x-if="post.special_type === 'stock_alert'">
                                <div>
                                    <div class="stock-alert-card border border-orange-200 rounded-xl p-5 mb-4 relative overflow-hidden">
                                        <!-- Icono de fondo -->
                                        <div class="absolute -top-1 -right-1 w-8 h-8 bg-orange-200 rounded-full opacity-30"></div>
                                        <div class="relative z-10">
                                            <div class="flex items-start space-x-4">
                                                <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center shadow-md">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <h4 class="font-bold text-orange-800 text-lg" x-text="post.alert_title"></h4>
                                                        <span class="px-2 py-1 bg-orange-200 text-orange-800 text-xs font-bold rounded-full">OFERTA</span>
                                                    </div>
                                                    <p class="text-orange-700 font-medium" x-text="post.alert_content"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Detalles del producto -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <template x-for="(value, key) in post.product_details" :key="key">
                                                <div>
                                                    <span class="font-medium text-gray-700" x-text="key + ':'"></span>
                                                    <span class="text-gray-600 ml-2" x-text="value"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Imagen del post mejorada (simulada) -->
                            <template x-if="post.has_image && post.image_type === 'chart'">
                                <div class="post-image-container mt-4 h-48 bg-gray-100">
                                    <div class="aspect-w-16 aspect-h-9 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center h-full">
                                        <div class="post-image-overlay"></div>
                                        <div class="text-center text-white relative z-10">
                                            <div class="work-completed-badge px-3 py-1 rounded-full mb-3 inline-block">
                                                📈 REPORTE MENSUAL
                                            </div>
                                            <svg class="w-8 h-8 mx-auto mb-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-lg font-bold">Reporte de Ventas - Junio 2025</p>
                                            <p class="text-blue-100 font-semibold">+23% vs mes anterior 🚀</p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Grid de imágenes trabajo completado mejorado -->
                            <template x-if="post.has_image && post.image_type === 'work_completed'">
                                <div class="post-image-container mt-4">
                                    <div class="grid grid-cols-2 gap-3 h-40">
                                        <div class="aspect-square bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center rounded-xl relative overflow-hidden">
                                            <div class="post-image-overlay"></div>
                                            <div class="text-center text-white relative z-10">
                                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                                <p class="text-sm font-bold">Volantes Offset</p>
                                                <p class="text-xs opacity-90">Couché 150g</p>
                                            </div>
                                        </div>
                                        <div class="aspect-square bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center rounded-xl relative overflow-hidden">
                                            <div class="post-image-overlay"></div>
                                            <div class="text-center text-white relative z-10">
                                                <div class="work-completed-badge px-2 py-1 rounded-full text-xs mb-1">
                                                    ✅ COMPLETADO
                                                </div>
                                                <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <p class="text-sm font-bold">50,000 uds</p>
                                                <p class="text-xs opacity-90">Cliente satisfecho</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Acciones del Post -->
                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span class="flex items-center" x-show="post.likes_count">
                                        <svg class="w-4 h-4 text-blue-500 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M7.493 18.75c-.425 0-.82-.236-.975-.632A7.48 7.48 0 016 15.375c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558-.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23h-.777zM2.331 10.977a11.969 11.969 0 00-.831 4.398 12 12 0 00.52 3.507c.26.85 1.084 1.368 1.973 1.368H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 01-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227z"/>
                                        </svg>
                                        <span x-text="`${post.likes_count} Me gusta`"></span>
                                    </span>
                                    <span x-text="`${post.comments_count} Comentarios`" x-show="post.comments_count"></span>
                                    <span x-text="`${post.shares_count} Compartir`" x-show="post.shares_count"></span>
                                    <span x-text="`${post.orders_count} Hacer Pedido`" x-show="post.orders_count"></span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-4 gap-2" x-show="!post.show_order_button">
                                <button @click="$wire.likePost(post.id)" 
                                        :key="`like-${post.id}`"
                                        class="social-action-btn flex items-center justify-center py-3 px-3 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors"
                                        :class="{ 'text-blue-600 bg-blue-50 font-semibold': post.user_liked }"
                                        :disabled="$wire.isLoadingPosts">
                                    
                                    <!-- Normal like icon -->
                                    <svg wire:loading.remove wire:target="likePost" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                    
                                    <!-- Loading spinner for like -->
                                    <svg wire:loading wire:target="likePost" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    
                                    <span class="text-sm font-medium" wire:loading.remove wire:target="likePost">👍 Me gusta</span>
                                    <span class="text-sm font-medium" wire:loading wire:target="likePost">⏳ ...</span>
                                </button>
                                <button class="social-action-btn flex items-center justify-center py-3 px-3 text-gray-600 hover:bg-green-50 hover:text-green-600 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="text-sm font-medium">💬 Comentar</span>
                                </button>
                                <button class="social-action-btn flex items-center justify-center py-3 px-3 text-gray-600 hover:bg-purple-50 hover:text-purple-600 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                    </svg>
                                    <span class="text-sm font-medium">🔗 Compartir</span>
                                </button>
                                <button class="social-action-btn flex items-center justify-center py-3 px-3 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <span class="text-sm font-medium">📤 Enviar</span>
                                </button>
                            </div>
                            
                            <!-- Botones especiales para ofertas -->
                            <div class="grid grid-cols-4 gap-2" x-show="post.show_order_button">
                                <button class="flex items-center justify-center py-2 px-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                    </svg>
                                    <span class="text-sm">Me gusta</span>
                                </button>
                                <button class="flex items-center justify-center py-2 px-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="text-sm">Comentar</span>
                                </button>
                                <button class="flex items-center justify-center py-2 px-3 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors font-semibold">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v4a2 2 0 01-2 2H9a2 2 0 01-2-2v-4m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"/>
                                    </svg>
                                    <span class="text-sm">Hacer Pedido</span>
                                </button>
                                <button class="flex items-center justify-center py-2 px-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                    </svg>
                                    <span class="text-sm">Compartir</span>
                                </button>
                            </div>
                        </div>

                        <!-- Sección de Comentarios -->
                        <template x-if="post.show_comments && post.recent_comments">
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="space-y-3">
                                    <!-- Comentario -->
                                    <template x-for="(comment, commentIndex) in post.recent_comments" :key="comment.id || `comment-${commentIndex}`">
                                        <div class="flex space-x-3">
                                            <div :class="`w-8 h-8 ${comment.avatar_bg} rounded-full flex items-center justify-center flex-shrink-0 social-post-avatar`">
                                                <span class="text-white text-xs font-semibold" x-text="comment.avatar_initials"></span>
                                            </div>
                                            <div class="flex-1">
                                                <div class="comment-bubble rounded-lg p-3">
                                                    <p class="text-sm">
                                                        <span class="font-semibold text-gray-900" x-text="comment.author"></span>
                                                        <span class="text-gray-700 ml-1" x-text="comment.content"></span>
                                                    </p>
                                                </div>
                                                <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                                                    <button class="hover:text-gray-700">Me gusta</button>
                                                    <button class="hover:text-gray-700">Responder</button>
                                                    <span x-text="comment.time"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Formulario para comentar -->
                                    <div class="flex space-x-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-white text-xs font-semibold">TU</span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="bg-gray-50 rounded-lg p-3">
                                                <input 
                                                    type="text" 
                                                    placeholder="Escribe un comentario..." 
                                                    class="w-full bg-transparent text-sm placeholder-gray-500 focus:outline-none">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </article>
            </template>

            <!-- Load More Button con Livewire -->
            <div class="text-center py-6">
                <button wire:click="loadMorePosts" 
                        wire:loading.attr="disabled" 
                        class="bg-gray-100 hover:bg-gray-200 disabled:bg-gray-300 disabled:cursor-not-allowed text-gray-700 font-medium px-6 py-3 rounded-lg transition-colors flex items-center mx-auto">
                    
                    <!-- Normal state -->
                    <svg wire:loading.remove wire:target="loadMorePosts" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    
                    <!-- Loading state -->
                    <svg wire:loading wire:target="loadMorePosts" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <span wire:loading.remove wire:target="loadMorePosts">Cargar más publicaciones</span>
                    <span wire:loading wire:target="loadMorePosts">Cargando posts...</span>
                </button>
                
                <!-- Progress indicator during loading -->
                <div wire:loading wire:target="loadMorePosts" class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 65%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Buscando nuevas publicaciones en la red...</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Derecho Social -->
        <div class="space-y-6">
            <!-- Panel de Notificaciones -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a5.5 5.5 0 10-7.78 0L12 17h3zM9.5 9.5a3 3 0 106 0 3 3 0 00-6 0z"/>
                            </svg>
                            Notificaciones
                        </div>
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full pulse-animation" x-text="`${notifications.filter(n => n.unread).length}`"></span>
                    </h3>
                </div>
                <div class="p-0 max-h-64 overflow-y-auto">
                    <template x-for="notification in notifications" :key="notification.id">
                        <div class="flex items-start space-x-3 p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0"
                             :class="{ 'bg-blue-50': notification.unread }">
                            <div :class="`w-8 h-8 ${notification.avatar_bg} rounded-full flex items-center justify-center social-post-avatar flex-shrink-0`">
                                <span class="text-white text-xs font-semibold" x-text="notification.avatar_initials"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900" x-text="notification.message" :class="{ 'font-semibold': notification.unread }"></p>
                                <p class="text-xs text-gray-500 mt-1" x-text="notification.time"></p>
                            </div>
                            <div x-show="notification.unread" class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>
                        </div>
                    </template>
                    <div class="p-4 text-center border-t border-gray-100">
                        <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            Ver todas las notificaciones
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sugerencias de Conexión -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <svg class="w-5 h-5 mr-2 text-blue-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Empresas Sugeridas
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="company in suggestedCompanies" :key="company.name">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div :class="`w-8 h-8 ${company.avatar_bg} rounded-full flex items-center justify-center`">
                                    <span class="text-white text-sm font-semibold" x-text="company.avatar_initials"></span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm" x-text="company.name"></p>
                                    <p class="text-xs text-gray-500" x-text="company.location"></p>
                                </div>
                            </div>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1 rounded transition-colors">
                                Seguir
                            </button>
                        </div>
                    </template>

                    <button class="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium pt-2 border-t border-gray-100">
                        Ver todas las sugerencias
                    </button>
                </div>
            </div>

            <!-- Tendencias -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <svg class="w-5 h-5 mr-2 text-red-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                        </svg>
                        Tendencias
                    </h3>
                </div>
                <div class="p-6 space-y-2">
                    <template x-for="trend in trends" :key="trend.tag">
                        <div class="trending-tag flex items-center justify-between p-3">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm" x-text="trend.tag"></p>
                                <p class="text-xs text-gray-500" x-text="`${trend.posts} publicaciones`"></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-green-600 font-medium">🔥 Trending</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Chat Rápido -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <svg class="w-5 h-5 mr-2 text-green-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Chat Rápido
                    </h3>
                </div>
                <div class="p-0">
                    <!-- Lista de contactos activos -->
                    <div class="space-y-1">
                        <template x-for="contact in chatContacts" :key="contact.name">
                            <div class="flex items-center space-x-3 p-3 hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="relative">
                                    <div :class="`w-8 h-8 ${contact.avatar_bg} rounded-full flex items-center justify-center social-post-avatar`">
                                        <span class="text-white text-sm font-semibold" x-text="contact.avatar_initials"></span>
                                    </div>
                                    <div :class="`absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white ${contact.status === 'online' ? 'chat-status-online' : contact.status === 'away' ? 'chat-status-away' : 'chat-status-offline'}`"></div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm" x-text="contact.name"></p>
                                    <p class="text-xs text-gray-500" x-text="contact.last_seen"></p>
                                </div>
                                <div x-show="contact.unread" class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div x-data="{ show: @js(session('social-success') ? true : false), message: @js(session('social-success')) }" 
         x-show="show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50"
         style="display: none;">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="message"></span>
        </div>
    </div>
</div>