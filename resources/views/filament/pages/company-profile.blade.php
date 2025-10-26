<x-filament-panels::page>
    <!-- Estilos específicos para el perfil de empresa -->
    <style>
        /* Contenedor principal personalizado */
        .profile-layout {
            display: flex !important;
            position: relative !important;
            min-height: calc(100vh - 64px) !important;
            background-color: #f9fafb !important;
        }

        .profile-content {
            flex: 1 !important;
            padding: 24px !important;
            margin-right: 400px !important;
            overflow-y: auto !important;
        }

        .profile-sidebar {
            width: 400px !important;
            padding: 20px !important;
            position: absolute !important;
            right: 0 !important;
            top: 0 !important;
            z-index: 10 !important;
            min-height: 100% !important;
        }

        /* Dark theme styles */
        .dark .profile-layout {
            background-color: #111827 !important;
        }

        .dark .profile-content {
            background-color: #111827 !important;
        }
    </style>

    <div class="profile-layout">
        <!-- Contenido Principal -->
        <div class="profile-content">
            <div style="max-width: 100%; padding: 0 40px;">

                <!-- Banner y Avatar -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mb-6">
                    <!-- Banner -->
                    <div class="h-48 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                        @if($company->banner)
                            <img src="{{ asset('storage/' . $company->banner) }}" alt="Banner" class="w-full h-full object-cover">
                        @endif
                        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                    </div>

                    <!-- Profile Info -->
                    <div class="px-6 pb-6">
                        <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-6 -mt-16 relative">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 mb-4 sm:mb-0">
                                <div class="w-32 h-32 bg-white dark:bg-gray-800 rounded-full border-4 border-white dark:border-gray-700 shadow-lg overflow-hidden">
                                    @if($company->avatar)
                                        <img src="{{ asset('storage/' . $company->avatar) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="text-3xl font-bold text-gray-500 dark:text-gray-400">
                                                {{ strtoupper(substr($company->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Company Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white truncate">{{ $company->name }}</h1>
                                        @if($company->bio)
                                            <p class="text-gray-600 dark:text-gray-400 mt-2 max-w-2xl">{{ $company->bio }}</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="flex space-x-6 mt-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['posts_count'] }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Posts</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['followers_count'] }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Seguidores</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['following_count'] }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Siguiendo</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posts Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Publicaciones</h2>
                    </div>

                    <div class="divide-y divide-gray-200 dark:border-gray-700">
                        @forelse($this->posts as $post)
                            <div class="p-6">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($post->author->company && $post->author->company->avatar)
                                            <img src="{{ asset('storage/' . $post->author->company->avatar) }}"
                                                 alt="{{ $post->author->company->name }}"
                                                 class="h-10 w-10 rounded-full object-cover">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                                {{ strtoupper(substr($post->author->name ?? 'U', 0, 2)) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $post->author->name }}</h4>
                                            <span class="text-sm text-gray-400">•</span>
                                            <span class="text-sm text-gray-400">{{ $post->created_at->diffForHumans() }}</span>
                                        </div>

                                        <div class="mt-2">
                                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $post->content }}</p>
                                        </div>

                                        @if($post->reactions->count() > 0 || $post->comments->count() > 0)
                                            <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                                @if($post->reactions->count() > 0)
                                                    <span>{{ $post->reactions->count() }} reacciones</span>
                                                @endif
                                                @if($post->comments->count() > 0)
                                                    <span>{{ $post->comments->count() }} comentarios</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <p class="mt-2">No hay publicaciones aún</p>
                            </div>
                        @endforelse
                    </div>

                    @if($this->posts->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            {{ $this->posts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Derecho -->
        <aside class="profile-sidebar">
            <div style="space-y: 24px;">
                <!-- Contact Info Card -->
                @if($company->show_contact_info)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de Contacto</h3>

                        <div class="space-y-3">
                            @if($company->email)
                                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-envelope" class="w-5 h-5 flex-shrink-0" />
                                    <span class="text-sm">{{ $company->email }}</span>
                                </div>
                            @endif

                            @if($company->phone)
                                <div class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-phone" class="w-5 h-5 flex-shrink-0" />
                                    <span class="text-sm">{{ $company->phone }}</span>
                                </div>
                            @endif

                            @if($company->website)
                                <div class="flex items-center gap-3">
                                    <x-filament::icon icon="heroicon-o-globe-alt" class="w-5 h-5 flex-shrink-0 text-gray-600 dark:text-gray-400" />
                                    <a href="{{ $company->website }}" target="_blank" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 truncate">
                                        {{ $company->website }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-filament-panels::page>
