<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header con información de la empresa --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center space-x-4">
                @if(auth()->user()->company->logo)
                    <div class="flex-shrink-0">
                        <img class="h-16 w-16 rounded-lg object-cover" 
                             src="{{ Storage::url(auth()->user()->company->logo) }}" 
                             alt="{{ auth()->user()->company->name }}">
                    </div>
                @else
                    <div class="flex-shrink-0">
                        <div class="h-16 w-16 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                            <x-heroicon-o-building-office-2 class="h-8 w-8 text-gray-400"/>
                        </div>
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ auth()->user()->company->name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Configuración de empresa
                    </p>
                </div>
            </div>
        </div>

        {{-- Formulario de configuración --}}
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="flex justify-end mt-6">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Guardar Configuración
                </button>
            </div>
        </form>

        {{-- Estadísticas rápidas --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Estadísticas de la Empresa
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ auth()->user()->company->users()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Usuarios Activos
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ auth()->user()->company->documents()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Documentos Totales
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ auth()->user()->company->products()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Productos en Catálogo
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ auth()->user()->company->contacts()->count() }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Contactos
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>