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
            <div class="create-post mb-6">
                <form class="space-y-4">
                    <div>
                        <textarea 
                            rows="3"
                            class="w-full p-4 border border-gray-300 dark:border-gray-600 rounded-lg resize-vertical font-sans transition-colors focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-gray-800 dark:text-white"
                            placeholder="¿Qué quieres compartir con la comunidad de LitoPro? Promociones, trabajos terminados, consejos técnicos..."
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <button type="button" class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm">Imagen</span>
                            </button>
                            <button type="button" class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 00-2.828-2.828l-6.586 6.586a2 2 0 11-2.828-2.828L13.343 4.929a4 4 0 116.586 6.586L13.343 18.1a4 4 0 01-6.586-6.586z"/>
                                </svg>
                                <span class="text-sm">Archivo</span>
                            </button>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            Publicar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Social Feed -->
            <div class="divide-y divide-gray-200">
                <!-- Post 1 -->
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <img class="h-10 w-10 rounded-full" 
                             src="https://ui-avatars.com/api/?name=Carlos+Ventas&background=3b82f6&color=fff" 
                             alt="Carlos Ventas">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Carlos Ventas</h4>
                                <span class="text-sm text-gray-500">Litografía Demo</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-400">hace 2 horas</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                🎉 ¡Excelente mes de junio! Hemos superado nuestra meta de ventas en un 23%. Gracias a todos nuestros clientes que confían en nosotros para sus proyectos de impresión. ¡Seguimos creciendo juntos! 💪🏻 ✨
                            </p>
                            <div class="mt-4 flex items-center space-x-6">
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span class="text-sm">13 Me gusta</span>
                                </button>
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="text-sm">5 Comentarios</span>
                                </button>
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                    </svg>
                                    <span class="text-sm">Compartir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post 2 -->
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <img class="h-10 w-10 rounded-full" 
                             src="https://ui-avatars.com/api/?name=Papeleria+Central&background=10b981&color=fff" 
                             alt="Papelería Central">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Papelería Central</h4>
                                <span class="text-sm text-gray-500">Distribuidor</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-400">hace 5 horas</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                📦 NUEVA LLEGADA: Papel couche brillante 150g de excelente calidad. Stock limitado con 15% de descuento para pedidos mayores a 200 pliegos. ¡Aprovecha esta oferta especial! 🔥
                            </p>
                            <div class="mt-4 flex items-center space-x-6">
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span class="text-sm">12 Me gusta</span>
                                </button>
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="text-sm">0 Comentarios</span>
                                </button>
                                <button class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    👀 Hacer Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post 3 -->
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <img class="h-10 w-10 rounded-full" 
                             src="https://ui-avatars.com/api/?name=Jose+Produccion&background=f59e0b&color=fff" 
                             alt="José Producción">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">José Producción</h4>
                                <span class="text-sm text-gray-500">Litografía Demo</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-400">hace 8 horas</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                ✅ Trabajo terminado! Acabamos de finalizar la impresión de 50,000 volantes para la campaña de verano del cliente. Calidad offset en papel couche 150g con acabado brillante. El cliente quedó muy satisfecho con el resultado. 👍 📰
                            </p>
                            <div class="mt-4 flex items-center space-x-6">
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span class="text-sm">22 Me gusta</span>
                                </button>
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span class="text-sm">12 Comentarios</span>
                                </button>
                                <button class="flex items-center space-x-2 text-gray-500 hover:text-blue-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                                    </svg>
                                    <span class="text-sm">Compartir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>