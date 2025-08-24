<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $this->getTitle() }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Crea una nueva cotización paso a paso con la calculadora de cortes integrada.
                </p>
            </div>

            <form wire:submit="create">
                {{ $this->form }}

                <div class="mt-8 flex justify-end space-x-3">
                    <x-filament::button
                        type="submit"
                        size="lg"
                    >
                        <x-heroicon-m-document-plus class="w-5 h-5 mr-2" />
                        Crear Cotización
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Estilos personalizados para el wizard */
        .fi-wizard-step-header {
            @apply bg-white;
        }
        
        .fi-wizard-step-content {
            @apply bg-gray-50/50;
        }
        
        .fi-section {
            @apply bg-white;
        }
        
        /* Destacar campos calculados */
        .calculated-field {
            @apply bg-blue-50 border-blue-200;
        }
        
        /* Estilos para la sección de cortes */
        .cutting-section {
            @apply border-l-4 border-l-blue-500 pl-4;
        }
    </style>
</x-filament-panels::page>