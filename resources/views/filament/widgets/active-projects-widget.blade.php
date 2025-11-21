<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-folder class="w-5 h-5 text-primary-600" />
                <span>Proyectos Activos</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::link
                :href="route('filament.admin.pages.projects')"
                tag="a"
                icon="heroicon-o-arrow-right"
                icon-position="after"
            >
                Ver todos
            </x-filament::link>
        </x-slot>

        @php
            $projects = $this->getActiveProjects();
        @endphp

        @if(count($projects) > 0)
            <div class="space-y-3">
                @foreach($projects as $project)
                    <a href="{{ route('filament.admin.pages.project-detail', ['code' => $project['code']]) }}"
                       class="block p-4 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $project['code'] }}
                                    </h3>
                                    <span class="px-2 py-1 text-xs rounded-full {{
                                        match($project['status']) {
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'in_production' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'sent' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        }
                                    }}">
                                        {{
                                            match($project['status']) {
                                                'draft' => 'Borrador',
                                                'sent' => 'Enviado',
                                                'approved' => 'Aprobado',
                                                'in_production' => 'En Producción',
                                                'completed' => 'Completado',
                                                'cancelled' => 'Cancelado',
                                                default => 'Desconocido',
                                            }
                                        }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    Cliente: {{ $project['clientName'] ?? 'Sin cliente' }}
                                </p>

                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                        {{ $project['documentsCount'] }} docs
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-shopping-cart class="w-4 h-4" />
                                        {{ $project['purchaseOrdersCount'] }} pedidos
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-cog class="w-4 h-4" />
                                        {{ $project['productionOrdersCount'] }} producción
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        ${{ number_format($project['totalAmount'], 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500">
                                        {{ $project['getCompletionPercentage']() }}% completado
                                    </p>
                                </div>

                                {{-- Progress bar --}}
                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all"
                                         style="width: {{ $project['getCompletionPercentage']() }}%"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-heroicon-o-folder class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">No hay proyectos activos</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                    Los proyectos se crean automáticamente al asignar un código de referencia a una cotización
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
