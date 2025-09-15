<div 
    x-data="{ 
        showCreatePost: @entangle('showCreatePost'),
        posts: @js($posts ?? []),
        postTypes: @js($postTypes ?? [])
    }"
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
                        <span class="font-semibold text-gray-900 dark:text-white text-lg">💬 Compartir en la Red Social</span>
                    </div>
                </div>
            </x-slot>
            
            <!-- Create Post Form -->
            <div class="create-post mb-6" x-data="{ showForm: false, postType: 'news' }">
                <div class="bg-gray-50 rounded-lg p-4">
                    <!-- Collapsed state -->
                    <div x-show="!showForm" @click="showForm = true" class="cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs">
                                {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 2)) : '??' }}
                            </div>
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
            </div>

            <!-- Social Feed -->
            <div class="divide-y divide-gray-200">
                @forelse($posts as $post)
                <div class="p-6" x-data="{ showComments: false }">
                    <div class="flex items-start space-x-3">
                        <!-- Avatar -->
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                            {{ $post['avatar_initials'] }}
                        </div>

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
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $post['content'] }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex items-center space-x-6">
                                <button
                                    wire:click="likePost({{ $post['id'] }})"
                                    class="flex items-center space-x-2 text-gray-500 hover:text-red-600 transition-colors {{ $post['user_liked'] ? 'text-red-600' : '' }}"
                                >
                                    <svg class="h-5 w-5" fill="{{ $post['user_liked'] ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span class="text-sm">{{ $post['likes_count'] }} Me gusta</span>
                                </button>

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

                            <!-- Comments Section -->
                            <div x-show="showComments" x-transition class="mt-4 space-y-3">
                                <!-- Existing Comments -->
                                @foreach($post['recent_comments'] as $comment)
                                <div class="pl-4 border-l-2 border-gray-200">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-6 w-6 rounded-full bg-gradient-to-br from-green-500 to-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                                            {{ strtoupper(substr($comment['author_name'], 0, 1)) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment['author_name'] }}</span>
                                        <span class="text-sm text-gray-400">{{ $comment['created_at_human'] }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 ml-8">{{ $comment['content'] }}</p>
                                </div>
                                @endforeach

                                @if($post['comments_count'] > 3)
                                <button class="text-sm text-blue-600 hover:text-blue-800 ml-4">
                                    Ver los {{ $post['comments_count'] - 3 }} comentarios restantes...
                                </button>
                                @endif

                                <!-- Add Comment Form -->
                                <div class="mt-4 pl-4 border-l-2 border-gray-100">
                                    <form class="flex space-x-2" x-data="{ newComment: '' }">
                                        <div class="h-6 w-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs flex-shrink-0">
                                            {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 1)) : '?' }}
                                        </div>
                                        <div class="flex-1">
                                            <input
                                                type="text"
                                                x-model="newComment"
                                                placeholder="Escribe un comentario..."
                                                class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="if(newComment.trim()) {
                                                // Here we would call API to add comment
                                                console.log('Adding comment:', newComment);
                                                newComment = '';
                                            }"
                                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
                                        >
                                            Comentar
                                        </button>
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