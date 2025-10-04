<div class="deadlines-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-warning-100 dark:bg-warning-500/10">
                            <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Próximos Vencimientos</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Entregas programadas</p>
                        </div>
                    </div>
                    <x-filament::badge color="warning" size="lg">
                        3
                    </x-filament::badge>
                </div>
            </x-slot>

            <div class="space-y-3 mt-4">
                <!-- Deadline HOY - Crítico -->
                <div class="group relative overflow-hidden rounded-xl border transition-all duration-200 hover:shadow-md
                    bg-gradient-to-r from-red-50 to-red-50/50 border-red-200 hover:border-red-300
                    dark:from-red-950/30 dark:to-red-950/10 dark:border-red-900 dark:hover:border-red-800">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-filament::icon icon="heroicon-m-clock" class="w-4 h-4 text-red-600 dark:text-red-400 animate-pulse" />
                                    <h4 class="text-sm font-semibold text-red-900 dark:text-red-100">
                                        Catálogo Fashion
                                    </h4>
                                    <x-filament::badge color="danger" size="sm">
                                        HOY
                                    </x-filament::badge>
                                </div>

                                <div class="flex items-center gap-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-document-text" class="w-4 h-4 text-red-600 dark:text-red-400" />
                                        <span class="font-medium text-red-700 dark:text-red-300">ORD-2024-045</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 text-red-600 dark:text-red-400" />
                                        <span class="text-red-700 dark:text-red-300">28 Jun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-200 dark:bg-gray-800">
                        <div class="h-full bg-red-500 w-full"></div>
                    </div>
                </div>

                <!-- Deadline MAÑANA -->
                <div class="group relative overflow-hidden rounded-xl border transition-all duration-200 hover:shadow-md
                    bg-gradient-to-r from-orange-50 to-orange-50/50 border-orange-200 hover:border-orange-300
                    dark:from-orange-950/30 dark:to-orange-950/10 dark:border-orange-900 dark:hover:border-orange-800">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-filament::icon icon="heroicon-m-clock" class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                    <h4 class="text-sm font-semibold text-orange-900 dark:text-orange-100">
                                        Volantes Restaurante
                                    </h4>
                                    <x-filament::badge color="warning" size="sm">
                                        Mañana
                                    </x-filament::badge>
                                </div>

                                <div class="flex items-center gap-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-document-text" class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                        <span class="font-medium text-orange-700 dark:text-orange-300">COT-2024-089</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                        <span class="text-orange-700 dark:text-orange-300">28 Jun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-200 dark:bg-gray-800">
                        <div class="h-full bg-orange-500 transition-all duration-300" style="width: 85%"></div>
                    </div>
                </div>

                <!-- Deadline EN 2 DÍAS -->
                <div class="group relative overflow-hidden rounded-xl border transition-all duration-200 hover:shadow-md
                    bg-gradient-to-r from-yellow-50 to-yellow-50/50 border-yellow-200 hover:border-yellow-300
                    dark:from-yellow-950/30 dark:to-yellow-950/10 dark:border-yellow-900 dark:hover:border-yellow-800">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-filament::icon icon="heroicon-m-clock" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                    <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100">
                                        Folletos Clínica
                                    </h4>
                                    <x-filament::badge color="warning" size="sm">
                                        En 2 días
                                    </x-filament::badge>
                                </div>

                                <div class="flex items-center gap-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-document-text" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        <span class="font-medium text-yellow-700 dark:text-yellow-300">ORD-2024-044</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        <span class="text-yellow-700 dark:text-yellow-300">29 Jun</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gray-200 dark:bg-gray-800">
                        <div class="h-full bg-yellow-500 transition-all duration-300" style="width: 60%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-3">
                    <x-filament::button
                        color="warning"
                        size="sm"
                        tag="a"
                        href="{{ route('filament.admin.resources.documents.index') }}"
                        icon="heroicon-m-plus"
                        class="justify-center"
                    >
                        Nueva Entrega
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        size="sm"
                        tag="a"
                        href="{{ route('filament.admin.resources.documents.index') }}"
                        icon="heroicon-m-calendar-days"
                        outlined
                        class="justify-center"
                    >
                        Ver Calendario
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>