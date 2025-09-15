<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company->name }} - Perfil de Empresa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header/Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="/admin/home" class="text-blue-600 hover:text-blue-700 font-medium">
                        ← Volver al Feed
                    </a>
                </div>
                @auth
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ auth()->user()->name }}</span>
                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </span>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Banner y Avatar -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <!-- Banner -->
            <div class="h-48 bg-gradient-to-r from-blue-500 to-purple-600 relative">
                @if($company->banner)
                    <img src="{{ Storage::url($company->banner) }}" alt="Banner" class="w-full h-full object-cover">
                @endif
                <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            </div>

            <!-- Profile Info -->
            <div class="px-6 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-6 -mt-16 relative">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 mb-4 sm:mb-0">
                        <div class="w-32 h-32 bg-white rounded-full border-4 border-white shadow-lg overflow-hidden">
                            @if($company->avatar)
                                <img src="{{ Storage::url($company->avatar) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-3xl font-bold text-gray-500">
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
                                <h1 class="text-3xl font-bold text-gray-900 truncate">{{ $company->name }}</h1>
                                @if($company->bio)
                                    <p class="text-gray-600 mt-2 max-w-2xl">{{ $company->bio }}</p>
                                @endif
                            </div>

                            <!-- Follow Button -->
                            @auth
                                @if(auth()->user()->company_id !== $company->id)
                                    <div class="mt-4 sm:mt-0">
                                        <button
                                            onclick="toggleFollow({{ $company->id }})"
                                            id="followButton"
                                            class="px-6 py-2 rounded-lg font-medium transition-colors {{ $isFollowing ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                                        >
                                            {{ $isFollowing ? 'Siguiendo' : 'Seguir' }}
                                        </button>
                                    </div>
                                @endif
                            @endauth
                        </div>

                        <!-- Stats -->
                        <div class="flex space-x-6 mt-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['posts_count'] }}</div>
                                <div class="text-sm text-gray-600">Posts</div>
                            </div>
                            <a href="/empresa/{{ $company->slug }}/seguidores" class="text-center hover:bg-gray-50 px-2 py-1 rounded">
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['followers_count'] }}</div>
                                <div class="text-sm text-gray-600">Seguidores</div>
                            </a>
                            <a href="/empresa/{{ $company->slug }}/siguiendo" class="text-center hover:bg-gray-50 px-2 py-1 rounded">
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['following_count'] }}</div>
                                <div class="text-sm text-gray-600">Siguiendo</div>
                            </a>
                        </div>

                        <!-- Contact Info -->
                        @if($company->show_contact_info)
                            <div class="flex flex-wrap gap-4 mt-4">
                                @if($company->email)
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $company->email }}
                                    </div>
                                @endif

                                @if($company->phone)
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $company->phone }}
                                    </div>
                                @endif

                                @if($company->website)
                                    <a href="{{ $company->website }}" target="_blank" class="flex items-center text-blue-600 hover:text-blue-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9m0 9c-5 0-9-4-9-9s4-9 9-9"/>
                                        </svg>
                                        Sitio Web
                                    </a>
                                @endif
                            </div>
                        @endif

                        <!-- Social Media -->
                        <div class="flex space-x-3 mt-4">
                            @if($company->facebook)
                                <a href="{{ $company->facebook }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($company->instagram)
                                <a href="{{ $company->instagram }}" target="_blank" class="text-pink-600 hover:text-pink-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.395-3.44-1.098-.364-.258-.677-.577-.909-.935-.232-.357-.413-.739-.541-1.147-.128-.408-.192-.835-.192-1.281 0-1.297.395-2.448 1.098-3.44.258-.364.577-.677.935-.909.357-.232.739-.413 1.147-.541.408-.128.835-.192 1.281-.192 1.297 0 2.448.395 3.44 1.098.364.258.677.577.909.935.232.357.413.739.541 1.147.128.408.192.835.192 1.281 0 1.297-.395 2.448-1.098 3.44-.258.364-.577.677-.935.909-.357.232-.739.413-1.147.541-.408.128-.835.192-1.281.192z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($company->twitter)
                                <a href="{{ $company->twitter }}" target="_blank" class="text-gray-900 hover:text-gray-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($company->linkedin)
                                <a href="{{ $company->linkedin }}" target="_blank" class="text-blue-700 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Feed -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Publicaciones</h2>
            </div>

            @if($posts->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($posts as $post)
                        <div class="p-6">
                            <div class="flex space-x-3">
                                <!-- Author Avatar -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-semibold">
                                            {{ strtoupper(substr($post->author->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <!-- Post Header -->
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-medium text-gray-900">{{ $post->author->name }}</h3>
                                        <span class="text-sm text-gray-500">•</span>
                                        <span class="text-sm text-gray-500">{{ $post->created_at->diffForHumans() }}</span>
                                    </div>

                                    <!-- Post Content -->
                                    <div class="mt-2">
                                        @if($post->title)
                                            <h4 class="font-semibold text-gray-900 mb-2">{{ $post->title }}</h4>
                                        @endif
                                        <p class="text-gray-700">{{ $post->content }}</p>

                                        @if($post->image_path)
                                            <div class="mt-3">
                                                <img src="{{ Storage::url($post->image_path) }}" alt="Post image" class="rounded-lg max-w-full h-auto">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Post Actions -->
                                    <div class="mt-4 flex items-center space-x-6 text-sm text-gray-500">
                                        <button class="flex items-center space-x-2 hover:text-blue-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <span>{{ $post->reactions->count() }} Me gusta</span>
                                        </button>

                                        <button class="flex items-center space-x-2 hover:text-blue-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            <span>{{ $post->comments->count() }} Comentarios</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay publicaciones</h3>
                    <p class="text-gray-600">Esta empresa aún no ha publicado nada.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        async function toggleFollow(companyId) {
            const button = document.getElementById('followButton');
            const followersCountElement = document.querySelector('[href*="/seguidores"] .text-2xl');

            // Deshabilitar botón durante la petición
            button.disabled = true;
            button.textContent = 'Procesando...';
            button.className = 'px-6 py-2 rounded-lg font-medium transition-colors bg-gray-400 text-white cursor-not-allowed';

            try {
                const response = await fetch(`/api/companies/${companyId}/follow`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar botón según el resultado
                    if (data.action === 'followed') {
                        button.textContent = 'Siguiendo';
                        button.className = 'px-6 py-2 rounded-lg font-medium transition-colors bg-gray-200 text-gray-700 hover:bg-gray-300';
                    } else {
                        button.textContent = 'Seguir';
                        button.className = 'px-6 py-2 rounded-lg font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700';
                    }

                    // Actualizar contador de seguidores
                    if (followersCountElement && data.followers_count !== undefined) {
                        followersCountElement.textContent = data.followers_count;
                    }

                    // Mostrar mensaje de éxito
                    showNotification(data.message, 'success');
                } else {
                    throw new Error(data.message || 'Error al procesar la solicitud');
                }
            } catch (error) {
                console.error('Error:', error);

                // Restaurar estado original del botón
                const isFollowing = button.textContent.includes('Siguiendo') || button.className.includes('bg-gray-200');
                if (isFollowing) {
                    button.textContent = 'Siguiendo';
                    button.className = 'px-6 py-2 rounded-lg font-medium transition-colors bg-gray-200 text-gray-700 hover:bg-gray-300';
                } else {
                    button.textContent = 'Seguir';
                    button.className = 'px-6 py-2 rounded-lg font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700';
                }

                showNotification(error.message || 'Error al procesar la solicitud', 'error');
            } finally {
                button.disabled = false;
            }
        }


        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animar entrada
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Remover después de 4 segundos
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }
    </script>
</body>
</html>