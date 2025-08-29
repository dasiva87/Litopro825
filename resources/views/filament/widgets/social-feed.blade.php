<div 
    x-data="{ 
        showCreatePost: @entangle('showCreatePost'),
        posts: @js($posts),
        postTypes: @js($postTypes)
    }"
    class="fi-wi-social-feed"
>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-users class="h-6 w-6 text-primary-500" />
                        <span class="font-semibold text-gray-900 dark:text-white text-lg">Red Social LitoPro</span>
                    </div>
                    <x-filament::button
                        wire:click="toggleCreatePost"
                        size="sm"
                        color="primary"
                        icon="heroicon-o-plus"
                    >
                        Nueva Publicación
                    </x-filament::button>
                </div>
            </x-slot>
            
            <!-- Crear Post -->
            <div x-show="showCreatePost" x-transition class="create-post mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b-2 border-primary-500">
                    ✍️ Compartir en la Red Social
                </h3>
                
                <form wire:submit="createPost" class="space-y-4">
                    <!-- Tipo de Publicación -->
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="newPostType">
                                @foreach($postTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    
                    <!-- Contenido -->
                    <div>
                        <textarea 
                            wire:model="newPostContent"
                            rows="4"
                            class="w-full min-h-[100px] p-4 border border-gray-300 dark:border-gray-600 rounded-lg resize-vertical font-sans transition-colors focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-gray-800 dark:text-white"
                            placeholder="¿Qué quieres compartir con la comunidad de LitoPro? Promociones, trabajos terminados, consejos técnicos..."
                        ></textarea>
                        @error('newPostContent') 
                            <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Acciones -->
                    <div class="create-post-actions flex justify-between items-center pt-4 gap-4">
                        <div class="post-options flex gap-2">
                            <div class="post-option p-2 border border-gray-300 dark:border-gray-600 rounded cursor-pointer transition-all hover:bg-primary-500 hover:text-white hover:border-primary-500" title="Agregar imagen">
                                📷
                            </div>
                            <div class="post-option p-2 border border-gray-300 dark:border-gray-600 rounded cursor-pointer transition-all hover:bg-primary-500 hover:text-white hover:border-primary-500" title="Agregar archivo">
                                📎
                            </div>
                            <div class="post-option p-2 border border-gray-300 dark:border-gray-600 rounded cursor-pointer transition-all hover:bg-primary-500 hover:text-white hover:border-primary-500" title="Marcar como promoción">
                                🎉
                            </div>
                            <div class="post-option p-2 border border-gray-300 dark:border-gray-600 rounded cursor-pointer transition-all hover:bg-primary-500 hover:text-white hover:border-primary-500" title="Marcar como trabajo terminado">
                                ✅
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <x-filament::button 
                                type="button"
                                wire:click="toggleCreatePost"
                                color="gray"
                                size="sm"
                            >
                                Cancelar
                            </x-filament::button>
                            <x-filament::button 
                                type="submit"
                                color="success"
                                size="sm"
                            >
                                Publicar
                            </x-filament::button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Feed de Posts -->
            <div class="posts-feed space-y-4" id="postsFeed">
                @forelse($posts as $post)
                    <!-- Post {{ $post['id'] }} -->
                    <div class="post bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700 transition-all hover:shadow-md hover:-translate-y-0.5">
                        <!-- Post Header -->
                        <div class="post-header flex items-center mb-4">
                            <div class="post-avatar w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center text-white font-bold mr-4">
                                {{ $post['avatar_initials'] }}
                            </div>
                            <div class="post-info flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $post['company_name'] }}</h4>
                                    <x-filament::badge color="{{ $post['post_type_color'] }}" size="sm">
                                        {{ $post['post_type_label'] }}
                                    </x-filament::badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Por {{ $post['author_name'] }} • {{ $post['created_at_human'] }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="post-content mb-4 text-gray-800 dark:text-gray-200 leading-relaxed">
                            {{ $post['content'] }}
                        </div>
                        
                        <!-- Post Actions -->
                        <div class="post-actions flex gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button 
                                wire:click="likePost({{ $post['id'] }})"
                                class="post-action flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all text-sm {{ $post['user_liked'] ? 'text-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'text-gray-600 dark:text-gray-400 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20' }}"
                            >
                                <span class="{{ $post['user_liked'] ? 'scale-110' : '' }}">👍</span>
                                <span>{{ $post['likes_count'] }} Me gusta</span>
                            </button>
                            
                            <div class="post-action flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 hover:text-gray-800 dark:hover:bg-gray-700">
                                💬 {{ $post['comments_count'] }} Comentarios
                            </div>
                            
                            <div class="post-action flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 hover:text-gray-800 dark:hover:bg-gray-700">
                                📤 Compartir
                            </div>
                        </div>
                        
                        <!-- Recent Comments -->
                        @if(count($post['recent_comments']) > 0)
                            <div class="recent-comments mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                @foreach($post['recent_comments'] as $comment)
                                    <div class="comment-item flex items-start gap-3 mb-3 last:mb-0">
                                        <div class="comment-avatar w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center text-white text-xs font-medium">
                                            {{ substr($comment['author_name'], 0, 1) }}
                                        </div>
                                        <div class="comment-content flex-1">
                                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment['author_name'] }}</p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $comment['content'] }}</p>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-3">{{ $comment['created_at_human'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📱</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">¡Sé el primero en publicar!</h3>
                        <p class="text-gray-600 dark:text-gray-400">Comparte ofertas, solicitudes o noticias con la comunidad de litografías.</p>
                        <x-filament::button 
                            wire:click="toggleCreatePost"
                            color="primary"
                            class="mt-4"
                        >
                            Crear mi primera publicación
                        </x-filament::button>
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>

<!-- Success message -->
@if(session()->has('social-success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 3000)"
         x-transition
         class="fixed top-4 right-4 bg-success-500 text-white p-4 rounded-lg shadow-lg z-50">
        {{ session('social-success') }}
    </div>
@endif

<style>
/* Estilos específicos del widget social */
.fi-wi-social-feed .create-post textarea:focus {
    outline: none;
    border-color: rgb(59 130 246);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.fi-wi-social-feed .post {
    animation: fadeInUp 0.3s ease-out;
}

.fi-wi-social-feed .post-action:hover {
    transform: translateY(-1px);
}

.fi-wi-social-feed .post-avatar {
    animation: pulse 2s infinite;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .8;
    }
}

/* Hover effects mejorados */
.fi-wi-social-feed .post-option:hover {
    transform: scale(1.05);
}

.fi-wi-social-feed .comment-item {
    animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

<script>
document.addEventListener('livewire:init', () => {
    // Listen for post creation events
    Livewire.on('post-created', (event) => {
        // Could trigger confetti or other celebration effects
        console.log('New post created!');
    });
    
    // Listen for post liked events  
    Livewire.on('post-liked', (event) => {
        const button = document.querySelector(`[wire\\:click="likePost(${event.postId})"]`);
        if (button) {
            // Add a small animation
            button.style.transform = 'scale(0.95)';
            setTimeout(() => {
                button.style.transform = 'scale(1)';
            }, 150);
        }
    });
});

// Auto-expand textarea
function autoExpandTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}
</script>