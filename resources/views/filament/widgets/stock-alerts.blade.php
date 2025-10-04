<div class="stock-alerts-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-danger-100 dark:bg-danger-500/10">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 text-danger-600 dark:text-danger-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Alertas de Stock</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Productos con stock bajo</p>
                        </div>
                    </div>
                    <x-filament::badge color="danger" size="lg">
                        2
                    </x-filament::badge>
                </div>
            </x-slot>

            <div class="space-y-3 mt-4">
                <!-- Producto Crítico 1 -->
                <div class="group relative overflow-hidden rounded-xl border transition-all duration-200 hover:shadow-md
                    bg-gradient-to-r from-red-50 to-red-50/50 border-red-200 hover:border-red-300
                    dark:from-red-950/30 dark:to-red-950/10 dark:border-red-900 dark:hover:border-red-800">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="text-sm font-semibold text-red-900 dark:text-red-100">
                                        Bond Blanco 75g
                                    </h4>
                                </div>

                                <div class="flex items-center gap-4 text-xs mb-3">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-cube" class="w-4 h-4 text-red-600 dark:text-red-400" />
                                        <span class="font-medium text-red-700 dark:text-red-300">15 pliegos</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-4 h-4 text-red-600 dark:text-red-400" />
                                        <span class="text-red-700 dark:text-red-300">70x100cm</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-red-200 dark:bg-red-900/30 rounded-full h-2 overflow-hidden">
                                        <div class="bg-red-600 dark:bg-red-500 h-2 rounded-full transition-all duration-300" style="width: 30%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-red-700 dark:text-red-300 whitespace-nowrap">30%</span>
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <x-filament::badge color="danger" size="sm">
                                    Crítico
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-200 dark:bg-gray-800">
                        <div class="h-full bg-red-500 transition-all duration-300" style="width: 30%"></div>
                    </div>
                </div>

                <!-- Producto Crítico 2 -->
                <div class="group relative overflow-hidden rounded-xl border transition-all duration-200 hover:shadow-md
                    bg-gradient-to-r from-yellow-50 to-yellow-50/50 border-yellow-200 hover:border-yellow-300
                    dark:from-yellow-950/30 dark:to-yellow-950/10 dark:border-yellow-900 dark:hover:border-yellow-800">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100">
                                        Couche 150g
                                    </h4>
                                </div>

                                <div class="flex items-center gap-4 text-xs mb-3">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-cube" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        <span class="font-medium text-yellow-700 dark:text-yellow-300">45 pliegos</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        <span class="text-yellow-700 dark:text-yellow-300">70x100cm</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-yellow-200 dark:bg-yellow-900/30 rounded-full h-2 overflow-hidden">
                                        <div class="bg-yellow-600 dark:bg-yellow-500 h-2 rounded-full transition-all duration-300" style="width: 30%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-yellow-700 dark:text-yellow-300 whitespace-nowrap">30%</span>
                                </div>
                            </div>

                            <div class="flex-shrink-0">
                                <x-filament::badge color="warning" size="sm">
                                    Bajo
                                </x-filament::badge>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-200 dark:bg-gray-800">
                        <div class="h-full bg-yellow-500 transition-all duration-300" style="width: 30%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-3">
                    <x-filament::button
                        color="danger"
                        size="sm"
                        tag="a"
                        href="{{ route('filament.admin.resources.products.index') }}"
                        icon="heroicon-m-shopping-cart"
                        class="justify-center"
                    >
                        Pedido Urgente
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        size="sm"
                        tag="a"
                        href="{{ route('filament.admin.pages.stock-management') }}"
                        icon="heroicon-m-archive-box"
                        outlined
                        class="justify-center"
                    >
                        Ver Inventario
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>