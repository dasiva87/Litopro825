<div class="fi-modal-content" x-data="{
    search: '',
    companies: {{ $companies->toJson() }},
    get filteredCompanies() {
        if (this.search.trim() === '') {
            return this.companies;
        }
        const searchLower = this.search.toLowerCase();
        return this.companies.filter(company => {
            const nameMatch = company.name.toLowerCase().includes(searchLower);
            const taxIdMatch = company.tax_id && company.tax_id.toLowerCase().includes(searchLower);
            return nameMatch || taxIdMatch;
        });
    }
}">
    @if($companies->isEmpty())
        <div class="text-center py-12">
            <x-filament::icon
                icon="heroicon-o-magnifying-glass"
                class="mx-auto h-12 w-12 text-gray-400"
            />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No se encontraron empresas</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No hay empresas disponibles en Grafired</p>
        </div>
    @else
        {{-- Buscador --}}
        <div class="mb-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Buscar por nombre o número de identificación..."
                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                />
            </div>
        </div>

        {{-- Contador de resultados --}}
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                <x-filament::icon icon="heroicon-m-building-office-2" class="inline h-4 w-4 mr-1.5 text-primary-500" />
                <span x-text="filteredCompanies.length"></span>
                <span x-text="filteredCompanies.length === 1 ? 'empresa disponible' : 'empresas disponibles'"></span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Haz clic en "Solicitar" para conectar
            </div>
        </div>

        {{-- Mensaje cuando no hay resultados --}}
        <div x-show="filteredCompanies.length === 0" class="text-center py-12">
            <x-filament::icon
                icon="heroicon-o-magnifying-glass"
                class="mx-auto h-12 w-12 text-gray-400"
            />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No se encontraron resultados</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Intenta con otros términos de búsqueda</p>
        </div>

        {{-- Grid de empresas --}}
        <div x-show="filteredCompanies.length > 0" style="display: flex; flex-wrap: wrap; gap: 0.75rem; max-height: 550px; overflow-y: auto; padding-right: 0.5rem;">
            <template x-for="company in filteredCompanies" :key="company.id">
                <div class="group relative rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 hover:shadow-md transition-all duration-200 overflow-hidden" style="flex: 0 0 calc(33.333% - 0.5rem); max-width: calc(33.333% - 0.5rem);">
                    {{-- Header con Badge --}}
                    <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            {{-- Avatar + Nombre --}}
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <template x-if="company.logo">
                                    <img :src="`/storage/${company.logo}`"
                                         :alt="company.name"
                                         class="h-10 w-10 rounded-lg object-cover ring-2 ring-white dark:ring-gray-700 shadow-sm flex-shrink-0">
                                </template>
                                <template x-if="!company.logo">
                                    <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-bold text-base shadow-md flex-shrink-0">
                                        <span x-text="company.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                </template>

                                <h4 class="font-bold text-sm text-gray-900 dark:text-white truncate" x-text="company.name"></h4>
                            </div>

                            {{-- Badge de tipo --}}
                            <template x-if="company.company_type">
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    :class="{
                                        'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20': company.company_type === 'litografia',
                                        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20': company.company_type === 'distribuidora',
                                        'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20': company.company_type === 'proveedor_insumos',
                                        'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20': company.company_type === 'papeleria',
                                        'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/20': company.company_type === 'agencia',
                                        'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20': !['litografia', 'distribuidora', 'proveedor_insumos', 'papeleria', 'agencia'].includes(company.company_type)
                                    }"
                                    x-text="{
                                        'litografia': 'Litografía',
                                        'distribuidora': 'Distribuidor',
                                        'proveedor_insumos': 'Proveedor',
                                        'papeleria': 'Papelería',
                                        'agencia': 'Agencia'
                                    }[company.company_type] || company.company_type.charAt(0).toUpperCase() + company.company_type.slice(1)"
                                ></span>
                            </template>
                        </div>
                    </div>

                    {{-- Body con Info --}}
                    <div class="px-4 py-3 space-y-2">
                        {{-- Metadata --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-400">
                            <template x-if="company.city || company.state || company.country">
                                <div class="flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-m-map-pin" class="h-3.5 w-3.5 text-gray-400" />
                                    <span x-text="company.city?.name || company.state?.name || company.country?.name"></span>
                                </div>
                            </template>

                            <template x-if="company.followers_count > 0">
                                <div class="flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-m-users" class="h-3.5 w-3.5 text-gray-400" />
                                    <span x-text="`${company.followers_count} ${company.followers_count === 1 ? 'seguidor' : 'seguidores'}`"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Botón --}}
                        <button
                            @click="$wire.sendSupplierRequest(company.id, null)"
                            class="inline-flex items-center justify-center gap-1.5 w-full px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" />
                            Solicitar como Proveedor
                        </button>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
