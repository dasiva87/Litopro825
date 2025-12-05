<div class="space-y-4">
    @if($companies->isEmpty())
        <div class="text-center py-12">
            <div class="text-gray-400 dark:text-gray-600">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No se encontraron resultados</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Intenta con otros filtros de búsqueda</p>
        </div>
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Se encontraron {{ $companies->count() }} empresas
        </div>

        <div class="grid gap-4 max-h-96 overflow-y-auto">
            @foreach($companies as $company)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-primary-500 dark:hover:border-primary-600 transition">
                    <div class="flex items-start gap-4">
                        {{-- Logo --}}
                        <div class="flex-shrink-0">
                            @if($company->logo)
                                <img src="{{ Storage::url($company->logo) }}" alt="{{ $company->name }}" class="h-16 w-16 rounded-lg object-cover">
                            @else
                                <div class="h-16 w-16 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-2xl font-bold">
                                    {{ substr($company->name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $company->name }}
                                    </h4>
                                    @if($company->bio)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ $company->bio }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Tipo de empresa --}}
                                @if($company->company_type)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if($company->company_type === 'litografia') bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200
                                        @elseif($company->company_type === 'distribuidora') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($company->company_type === 'proveedor_insumos') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @endif">
                                        @switch($company->company_type)
                                            @case('litografia') Litografía @break
                                            @case('distribuidora') Distribuidora @break
                                            @case('proveedor_insumos') Proveedor @break
                                            @case('agencia') Agencia @break
                                            @default {{ $company->company_type }}
                                        @endswitch
                                    </span>
                                @endif
                            </div>

                            {{-- Ubicación y contacto --}}
                            <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($company->city || $company->state || $company->country)
                                    <div class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>
                                            {{ collect([$company->city?->name, $company->state?->name, $company->country?->name])->filter()->join(', ') }}
                                        </span>
                                    </div>
                                @endif

                                @if($company->followers_count > 0)
                                    <div class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span>{{ $company->followers_count }} seguidores</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Acciones --}}
                            <div class="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    wire:click="$dispatch('open-modal', { id: 'request-{{ $company->id }}' })"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    Solicitar como Proveedor
                                </button>

                                @if($company->website)
                                    <a href="{{ $company->website }}" target="_blank"
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        Sitio Web
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Modal de confirmación integrado con Livewire --}}
                    <x-filament::modal id="request-{{ $company->id }}" width="md">
                        <x-slot name="heading">
                            Solicitar Relación Comercial
                        </x-slot>

                        <x-slot name="description">
                            ¿Deseas enviar una solicitud para agregar a <strong>{{ $company->name }}</strong> como proveedor?
                        </x-slot>

                        <div class="space-y-4">
                            <div>
                                <label for="message-{{ $company->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Mensaje (opcional)
                                </label>
                                <textarea
                                    id="message-{{ $company->id }}"
                                    wire:model="requestMessage"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                    placeholder="Cuéntales por qué quieres trabajar con ellos..."></textarea>
                            </div>
                        </div>

                        <x-slot name="footerActions">
                            <x-filament::button
                                color="gray"
                                x-on:click="close">
                                Cancelar
                            </x-filament::button>

                            <x-filament::button
                                wire:click="requestSupplier({{ $company->id }}, $wire.requestMessage)"
                                x-on:click="close">
                                Enviar Solicitud
                            </x-filament::button>
                        </x-slot>
                    </x-filament::modal>
                </div>
            @endforeach
        </div>
    @endif
</div>
