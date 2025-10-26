<div
    x-data="{
        showCreatePost: @entangle('showCreatePost'),
        posts: @js($posts ?? []),
        postTypes: @js($postTypes ?? []),
        notificationsEnabled: @entangle('enableRealTimeUpdates'),
        newNotifications: @entangle('newNotifications'),

        // Browser notifications
        initNotifications() {
            if ('Notification' in window && Notification.permission !== 'granted') {
                Notification.requestPermission();
            }
        },

        showBrowserNotification(message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('LitoPro Red Social', {
                    body: message,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'social-feed'
                });
            }
        }
    }"
    x-init="initNotifications()"
    wire:poll.30s="checkForUpdates"
    @new-social-activity.window="
        showBrowserNotification($event.detail.message);
        // Optional: Play notification sound
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSMFl');
    "
    class="fi-wi-social-feed"
>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <svg class="h-6 w-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white text-lg">💬 Red Social Empresarial</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Search Bar -->
                        <div class="flex items-center space-x-2">
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model="searchQuery"
                                    placeholder="Buscar posts, hashtags, empresas..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm w-64"
                                />
                                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                @if(!empty($searchQuery))
                                <button
                                    wire:click="clearSearch"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>

                        <!-- Real-time notifications toggle -->
                        <div class="relative">
                            <button
                                wire:click="toggleRealTimeUpdates"
                                class="flex items-center space-x-2 px-3 py-2 rounded-lg transition-colors {{ $enableRealTimeUpdates ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-300 text-gray-700 hover:bg-gray-400' }}"
                                title="{{ $enableRealTimeUpdates ? 'Notificaciones activadas' : 'Notificaciones desactivadas' }}"
                            >
                                <div class="relative">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19H6a2 2 0 01-2-2V7a2 2 0 012-2h11a2 2 0 012 2v4.5"/>
                                    </svg>
                                    @if($newNotifications > 0)
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                        {{ $newNotifications > 9 ? '9+' : $newNotifications }}
                                    </span>
                                    @endif
                                </div>
                            </button>

                            @if($newNotifications > 0)
                            <button
                                wire:click="markNotificationsAsRead"
                                class="absolute -bottom-8 left-0 text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap"
                            >
                                Marcar como visto
                            </button>
                            @endif
                        </div>

                        <button
                            wire:click="toggleFilters"
                            class="flex items-center space-x-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span class="text-sm font-medium">Filtros</span>
                        </button>
                    </div>
                </div>
            </x-slot>
            
            <!-- Create Post Form -->
            <div class="create-post mb-6" x-data="{ showForm: false, postType: 'news' }">
                <div class="bg-gray-50 rounded-lg p-4">
                    <!-- Collapsed state -->
                    <div x-show="!showForm" @click="showForm = true" class="cursor-pointer">
                        <div class="flex items-center space-x-3">
                            @if(auth()->user()->company && auth()->user()->company->avatar)
                                <img
                                    src="{{ asset('storage/' . auth()->user()->company->avatar) }}"
                                    alt="{{ auth()->user()->company->name }}"
                                    class="h-8 w-8 rounded-full object-cover border border-gray-200"
                                />
                            @else
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs">
                                    {{ auth()->user()->company ? strtoupper(substr(auth()->user()->company->name, 0, 2)) : '??' }}
                                </div>
                            @endif
                            <p class="text-gray-500 flex-1">¿Qué quieres compartir con la comunidad de LitoPro?</p>
                        </div>
                    </div>

                    <!-- Expanded form -->
                    <div x-show="showForm" x-transition class="space-y-4">
                        <form wire:submit.prevent="createPost" class="space-y-4">
                            <!-- Post Type Selector -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de publicación</label>
                                <select wire:model="newPostType" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @foreach($postTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Content -->
                            <div>
                                <textarea
                                    wire:model="newPostContent"
                                    rows="4"
                                    class="w-full p-4 border border-gray-300 dark:border-gray-600 rounded-lg resize-vertical font-sans transition-colors focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-gray-800 dark:text-white"
                                    placeholder="Comparte tu experiencia, promociones, trabajos terminados, consejos técnicos..."
                                ></textarea>
                                @error('newPostContent')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-4">
                                    <button type="button" class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm">Imagen</span>
                                    </button>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" @click="showForm = false" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                        Publicar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if (session()->has('social-success'))
                    <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('social-success') }}
                    </div>
                @endif

                @if (session()->has('notification-success'))
                    <div class="mt-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                        {{ session('notification-success') }}
                    </div>
                @endif
            </div>

            <!-- Advanced Filters Panel -->
            @if($showFilters)
            <div class="mb-6 bg-white border border-gray-200 rounded-lg p-4" x-transition>
                @if (session()->has('filter-success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('filter-success') }}
                    </div>
                @endif

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Filtros Avanzados</h3>
                    <button
                        wire:click="clearFilters"
                        class="text-sm text-gray-600 hover:text-gray-800"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Filter by Post Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de publicación</label>
                        <select wire:model="filterType" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Todos los tipos</option>
                            @foreach($postTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter by Company -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
                        <input
                            type="text"
                            wire:model="filterCompany"
                            placeholder="Nombre de la empresa..."
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Filter by Location -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación</label>
                        <input
                            type="text"
                            wire:model="filterLocation"
                            placeholder="Ciudad o estado..."
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Filter by Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rango de fechas</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input
                                type="date"
                                wire:model="filterDateFrom"
                                class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                title="Desde"
                            />
                            <input
                                type="date"
                                wire:model="filterDateTo"
                                class="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                title="Hasta"
                            />
                        </div>
                    </div>
                </div>

                <!-- Active Filters Display -->
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($filterType !== 'all')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                            Tipo: {{ $postTypes[$filterType] ?? $filterType }}
                            <button wire:click="$set('filterType', 'all')" class="ml-2 text-blue-600 hover:text-blue-800">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($filterCompany))
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                            Empresa: {{ $filterCompany }}
                            <button wire:click="$set('filterCompany', '')" class="ml-2 text-green-600 hover:text-green-800">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($filterLocation))
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800">
                            Ubicación: {{ $filterLocation }}
                            <button wire:click="$set('filterLocation', '')" class="ml-2 text-purple-600 hover:text-purple-800">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if(!empty($filterDateFrom) || !empty($filterDateTo))
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
                            @if(!empty($filterDateFrom) && !empty($filterDateTo))
                                Fechas: {{ $filterDateFrom }} - {{ $filterDateTo }}
                            @elseif(!empty($filterDateFrom))
                                Desde: {{ $filterDateFrom }}
                            @else
                                Hasta: {{ $filterDateTo }}
                            @endif
                            <button wire:click="$set('filterDateFrom', ''); $set('filterDateTo', '')" class="ml-2 text-yellow-600 hover:text-yellow-800">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endif
                </div>
            </div>
            @endif

            <!-- Popular Hashtags Panel -->
            @if(!empty($popularHashtags))
            <div class="mb-6 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-900 flex items-center">
                        <svg class="h-4 w-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                        </svg>
                        Hashtags Populares
                    </h3>
                    @if(!empty($searchQuery))
                    <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
                        Buscando: {{ $searchQuery }}
                    </span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($popularHashtags as $hashtag)
                    <button
                        wire:click="searchByHashtag('{{ $hashtag }}')"
                        class="inline-flex items-center px-3 py-1 bg-white border border-blue-200 rounded-full text-sm text-blue-700 hover:bg-blue-100 hover:border-blue-300 transition-colors"
                    >
                        #{{ $hashtag }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Social Feed -->
            <div class="divide-y divide-gray-200">
                @forelse($posts as $post)
                <div class="p-6" x-data="{ showComments: false }">
                    <div class="flex items-start space-x-3">
                        <!-- Avatar -->
                        @if($post['avatar_url'])
                            <img
                                src="{{ $post['avatar_url'] }}"
                                alt="{{ $post['company_name_full'] }}"
                                class="h-10 w-10 rounded-full object-cover border-2 border-white shadow-sm"
                                title="{{ $post['company_name_full'] }}"
                            />
                        @else
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                {{ $post['avatar_initials'] }}
                            </div>
                        @endif

                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $post['author_name'] }}</h4>
                                <span class="text-sm text-gray-500">{{ $post['company_name'] }}</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-400">{{ $post['created_at_human'] }}</span>

                                <!-- Post Type Badge -->
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $post['post_type_color'] }}-100 text-{{ $post['post_type_color'] }}-800">
                                    {{ $post['post_type_label'] }}
                                </span>
                            </div>

                            <!-- Content -->
                            <div class="mt-2">
                                <div
                                    class="text-sm text-gray-700 dark:text-gray-300 social-content"
                                    x-data
                                    @click="
                                        if ($event.target.classList.contains('hashtag')) {
                                            const hashtag = $event.target.getAttribute('data-hashtag');
                                            $wire.searchByHashtag(hashtag);
                                        }
                                    "
                                >
                                    {!! $post['formatted_content'] !!}
                                </div>

                                <!-- Post Hashtags -->
                                @if(!empty($post['hashtags']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($post['hashtags'] as $hashtag)
                                    <button
                                        wire:click="searchByHashtag('{{ $hashtag }}')"
                                        class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs text-gray-600 hover:text-gray-800 transition-colors"
                                    >
                                        #{{ $hashtag }}
                                    </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            <!-- Reactions Summary -->
                            @if($post['total_reactions_count'] > 0)
                            <div class="mt-3 pb-2 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex -space-x-1">
                                            @if(($post['reactions_count']['like'] ?? 0) > 0)
                                                <div class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center text-xs text-white">❤️</div>
                                            @endif
                                            @if(($post['reactions_count']['interested'] ?? 0) > 0)
                                                <div class="w-6 h-6 rounded-full bg-yellow-500 flex items-center justify-center text-xs text-white">💡</div>
                                            @endif
                                            @if(($post['reactions_count']['helpful'] ?? 0) > 0)
                                                <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center text-xs text-white">✅</div>
                                            @endif
                                            @if(($post['reactions_count']['contact_me'] ?? 0) > 0)
                                                <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-xs text-white">📞</div>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-500">{{ $post['total_reactions_count'] }} {{ $post['total_reactions_count'] == 1 ? 'reacción' : 'reacciones' }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $post['comments_count'] }} comentarios</div>
                                </div>
                            </div>
                            @endif

                            <!-- Actions -->
                            <div class="mt-4 flex items-center justify-between">
                                <!-- Reactions Group -->
                                <div class="flex items-center space-x-1" x-data="{ showReactions: false }">
                                    <!-- Main Like Button -->
                                    <button
                                        wire:click="toggleReaction({{ $post['id'] }}, 'like')"
                                        class="flex items-center space-x-1 px-3 py-1.5 rounded-lg transition-colors {{ $post['user_reaction'] === 'like' ? 'bg-red-50 text-red-600 border border-red-200' : 'text-gray-500 hover:bg-gray-50' }}"
                                    >
                                        <span class="text-sm">{{ $post['user_reaction'] === 'like' ? '❤️' : '🤍' }}</span>
                                        <span class="text-sm font-medium">Me gusta</span>
                                    </button>

                                    <!-- Reactions Dropdown Toggle -->
                                    <div class="relative">
                                        <button
                                            @click="showReactions = !showReactions"
                                            class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <!-- Reactions Dropdown -->
                                        <div
                                            x-show="showReactions"
                                            x-transition
                                            @click.away="showReactions = false"
                                            class="absolute bottom-full left-0 mb-2 bg-white rounded-lg shadow-lg border p-2 flex items-center space-x-2 z-10"
                                        >
                                            <button
                                                wire:click="toggleReaction({{ $post['id'] }}, 'like')"
                                                class="reaction-btn p-2 rounded-lg transition-transform hover:scale-110 {{ $post['user_reaction'] === 'like' ? 'bg-red-50' : 'hover:bg-gray-50' }}"
                                                title="Me gusta"
                                            >
                                                <span class="text-lg">❤️</span>
                                                @if(($post['reactions_count']['like'] ?? 0) > 0)
                                                    <span class="text-xs text-gray-600">{{ $post['reactions_count']['like'] }}</span>
                                                @endif
                                            </button>

                                            <button
                                                wire:click="toggleReaction({{ $post['id'] }}, 'interested')"
                                                class="reaction-btn p-2 rounded-lg transition-transform hover:scale-110 {{ $post['user_reaction'] === 'interested' ? 'bg-yellow-50' : 'hover:bg-gray-50' }}"
                                                title="Me interesa"
                                            >
                                                <span class="text-lg">💡</span>
                                                @if(($post['reactions_count']['interested'] ?? 0) > 0)
                                                    <span class="text-xs text-gray-600">{{ $post['reactions_count']['interested'] }}</span>
                                                @endif
                                            </button>

                                            <button
                                                wire:click="toggleReaction({{ $post['id'] }}, 'helpful')"
                                                class="reaction-btn p-2 rounded-lg transition-transform hover:scale-110 {{ $post['user_reaction'] === 'helpful' ? 'bg-green-50' : 'hover:bg-gray-50' }}"
                                                title="Útil"
                                            >
                                                <span class="text-lg">✅</span>
                                                @if(($post['reactions_count']['helpful'] ?? 0) > 0)
                                                    <span class="text-xs text-gray-600">{{ $post['reactions_count']['helpful'] }}</span>
                                                @endif
                                            </button>

                                            <button
                                                wire:click="toggleReaction({{ $post['id'] }}, 'contact_me')"
                                                class="reaction-btn p-2 rounded-lg transition-transform hover:scale-110 {{ $post['user_reaction'] === 'contact_me' ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                                                title="Quiero contactar"
                                            >
                                                <span class="text-lg">📞</span>
                                                @if(($post['reactions_count']['contact_me'] ?? 0) > 0)
                                                    <span class="text-xs text-gray-600">{{ $post['reactions_count']['contact_me'] }}</span>
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Other Actions -->
                                <div class="flex items-center space-x-4">
                                    <button
                                        @click="showComments = !showComments"
                                        class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span class="text-sm">{{ $post['comments_count'] }} Comentarios</span>
                                    </button>

                                    <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                        </svg>
                                        <span class="text-sm">Compartir</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <div x-show="showComments" x-transition class="mt-4 space-y-3">
                                @if (session()->has('comment-success-' . $post['id']))
                                    <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded text-sm">
                                        {{ session('comment-success-' . $post['id']) }}
                                    </div>
                                @endif

                                @if (session()->has('error'))
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <!-- Comments Display -->
                                @php
                                    $commentsToShow = $post['show_all_comments'] ? $post['all_comments'] : $post['recent_comments']->reverse();
                                @endphp

                                @foreach($commentsToShow as $comment)
                                <div class="pl-4 border-l-2 border-gray-200 bg-gray-50 rounded-r-lg p-3">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-2 flex-1">
                                            <div class="h-6 w-6 rounded-full bg-gradient-to-br from-green-500 to-blue-600 flex items-center justify-center text-white font-semibold text-xs flex-shrink-0">
                                                {{ strtoupper(substr($comment['author_name'], 0, 1)) }}
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment['author_name'] }}</span>
                                                    <span class="text-xs text-gray-500">{{ $comment['company_name'] }}</span>
                                                    <span class="text-xs text-gray-400">•</span>
                                                    <span class="text-xs text-gray-400">{{ $comment['created_at_human'] }}</span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $comment['content'] }}</p>
                                            </div>
                                        </div>
                                        @if($comment['can_delete'])
                                        <button
                                            wire:click="deleteComment({{ $comment['id'] }})"
                                            onclick="return confirm('¿Estás seguro de que quieres eliminar este comentario?')"
                                            class="ml-2 text-gray-400 hover:text-red-500 transition-colors"
                                            title="Eliminar comentario"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                @endforeach

                                <!-- Show/Hide All Comments Toggle -->
                                @if($post['comments_count'] > 3)
                                <div class="text-center">
                                    <button
                                        wire:click="toggleShowAllComments({{ $post['id'] }})"
                                        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        @if($post['show_all_comments'])
                                            Ocultar comentarios
                                        @else
                                            Ver los {{ $post['comments_count'] - 3 }} comentarios restantes...
                                        @endif
                                    </button>
                                </div>
                                @endif

                                <!-- Add Comment Form -->
                                <div class="mt-4 pl-4 border-l-2 border-blue-100 bg-blue-50 rounded-r-lg p-3">
                                    <form wire:submit.prevent="addComment({{ $post['id'] }})" class="space-y-2">
                                        <div class="flex space-x-2">
                                            @if(auth()->user()->company && auth()->user()->company->avatar)
                                                <img
                                                    src="{{ asset('storage/' . auth()->user()->company->avatar) }}"
                                                    alt="{{ auth()->user()->company->name }}"
                                                    class="h-6 w-6 rounded-full object-cover border border-gray-200 flex-shrink-0"
                                                />
                                            @else
                                                <div class="h-6 w-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs flex-shrink-0">
                                                    {{ auth()->user()->company ? strtoupper(substr(auth()->user()->company->name, 0, 1)) : '?' }}
                                                </div>
                                            @endif
                                            <div class="flex-1">
                                                <textarea
                                                    wire:model="newComments.{{ $post['id'] }}"
                                                    rows="2"
                                                    placeholder="Escribe un comentario profesional..."
                                                    class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                                    maxlength="500"
                                                ></textarea>
                                                @error('newComments.' . $post['id'])
                                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center ml-8">
                                            <span class="text-xs text-gray-500">
                                                {{ strlen($this->newComments[$post['id']] ?? '') }}/500 caracteres
                                            </span>
                                            <div class="flex space-x-2">
                                                <button
                                                    type="button"
                                                    wire:click="$set('newComments.{{ $post['id'] }}', '')"
                                                    class="px-3 py-1 text-xs text-gray-600 hover:text-gray-800 transition-colors"
                                                >
                                                    Cancelar
                                                </button>
                                                <button
                                                    type="submit"
                                                    class="px-4 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                                    {{ empty(trim($this->newComments[$post['id']] ?? '')) ? 'disabled' : '' }}
                                                >
                                                    Comentar
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No hay publicaciones aún.</p>
                    <p class="text-sm text-gray-400">¡Sé el primero en compartir algo con la comunidad!</p>
                </div>
                @endforelse
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>