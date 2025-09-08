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
                    @if(auth()->user()->company->subscription_plan)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                   {{ auth()->user()->company->subscription_plan === 'enterprise' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : 
                                      (auth()->user()->company->subscription_plan === 'pro' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : 
                                       'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100') }}">
                            Plan {{ ucfirst(auth()->user()->company->subscription_plan) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Formulario principal --}}
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="flex justify-end mt-6">
                @foreach($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
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

        {{-- Acciones rápidas --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Acciones Rápidas
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('filament.admin.resources.users.index') }}" 
                   class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-users class="h-6 w-6 text-blue-500 mr-3"/>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Gestionar Usuarios</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Agregar, editar o desactivar usuarios</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.documents.index') }}" 
                   class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-document-text class="h-6 w-6 text-green-500 mr-3"/>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Ver Documentos</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cotizaciones, facturas y más</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.products.index') }}" 
                   class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-cube class="h-6 w-6 text-purple-500 mr-3"/>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Gestionar Productos</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Catálogo de productos y servicios</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>