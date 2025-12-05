<div style="display: flex; flex-direction: column; gap: 1rem;">
    {{-- Buscador --}}
    <div style="position: relative;">
        <div style="position: absolute; top: 0; bottom: 0; left: 0; padding-left: 0.75rem; display: flex; align-items: center; pointer-events: none;">
            <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-5 w-5 text-gray-400" />
        </div>
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nombre o número de identificación..."
            style="display: block; width: 100%; padding: 0.625rem 0.75rem 0.625rem 2.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background-color: white; color: #111827; font-size: 0.875rem;"
        />
    </div>

    {{-- Contador de resultados --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
        <div style="font-size: 0.875rem; font-weight: 600; color: #1f2937;">
            <x-filament::icon icon="heroicon-m-building-office-2" class="h-4 w-4" style="display: inline; margin-right: 0.375rem; color: #0ea5e9;" />
            {{ $companies->count() }} {{ $companies->count() === 1 ? 'empresa disponible' : 'empresas disponibles' }}
        </div>
        <div style="font-size: 0.75rem; color: #6b7280;">
            Haz clic en "Solicitar" para conectar
        </div>
    </div>

    {{-- Mensaje cuando no hay resultados --}}
    @if($companies->isEmpty())
        <div style="text-align: center; padding: 3rem 0;">
            <x-filament::icon
                icon="heroicon-o-magnifying-glass"
                class="h-12 w-12"
                style="display: block; margin: 0 auto 0.5rem auto; width: 3rem; height: 3rem; color: #9ca3af;"
            />
            <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">No se encontraron resultados</h3>
            <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Intenta con otros términos de búsqueda</p>
        </div>
    @else
        {{-- Grid de empresas en 3 columnas --}}
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; max-height: 550px; overflow-y: auto; padding-right: 0.5rem;">
            @foreach($companies as $company)
                <div style="position: relative; flex: 0 0 calc(33.333% - 0.5rem); max-width: calc(33.333% - 0.5rem); border-radius: 0.75rem; border: 2px solid #e5e7eb; background-color: white; overflow: hidden; transition: all 0.2s;">
                    {{-- Header con Badge --}}
                    <div style="background: linear-gradient(to right, #f9fafb, white); padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                            {{-- Avatar + Nombre --}}
                            <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0;">
                                @if($company->logo)
                                    <img src="{{ Storage::url($company->logo) }}"
                                         alt="{{ $company->name }}"
                                         style="height: 2.5rem; width: 2.5rem; border-radius: 0.5rem; object-fit: cover; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex-shrink: 0;">
                                @else
                                    <div style="height: 2.5rem; width: 2.5rem; border-radius: 0.5rem; background: linear-gradient(to bottom right, #0ea5e9, #0284c7); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); flex-shrink: 0;">
                                        {{ strtoupper(substr($company->name, 0, 1)) }}
                                    </div>
                                @endif

                                <h4 style="font-weight: bold; font-size: 0.875rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $company->name }}
                                </h4>
                            </div>

                            {{-- Badge de tipo --}}
                            @if($company->company_type)
                                @php
                                    $typeColors = [
                                        'litografia' => ['bg' => '#fef2f2', 'text' => '#991b1b', 'ring' => '#fecaca'],
                                        'distribuidora' => ['bg' => '#f0fdf4', 'text' => '#166534', 'ring' => '#bbf7d0'],
                                        'proveedor_insumos' => ['bg' => '#fefce8', 'text' => '#854d0e', 'ring' => '#fef08a'],
                                        'papeleria' => ['bg' => '#eff6ff', 'text' => '#1e40af', 'ring' => '#bfdbfe'],
                                        'agencia' => ['bg' => '#faf5ff', 'text' => '#6b21a8', 'ring' => '#e9d5ff'],
                                    ];
                                    $typeValue = $company->company_type->value;
                                    $colors = $typeColors[$typeValue] ?? ['bg' => '#f9fafb', 'text' => '#374151', 'ring' => '#e5e7eb'];
                                    $typeLabel = match($typeValue) {
                                        'litografia' => 'Litografía',
                                        'distribuidora' => 'Distribuidor',
                                        'proveedor_insumos' => 'Proveedor',
                                        'papeleria' => 'Papelería',
                                        'agencia' => 'Agencia',
                                        default => ucfirst($typeValue)
                                    };
                                @endphp
                                <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; background-color: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['ring'] }};">
                                    {{ $typeLabel }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body con Info --}}
                    <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        {{-- Metadata --}}
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 1rem; font-size: 0.75rem; color: #6b7280;">
                            @if($company->city || $company->state || $company->country)
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <x-filament::icon icon="heroicon-m-map-pin" class="h-3.5 w-3.5" style="width: 0.875rem; height: 0.875rem; color: #9ca3af;" />
                                    <span>{{ $company->city?->name ?? ($company->state?->name ?? $company->country?->name) }}</span>
                                </div>
                            @endif

                            @if($company->followers_count > 0)
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    <x-filament::icon icon="heroicon-m-users" class="h-3.5 w-3.5" style="width: 0.875rem; height: 0.875rem; color: #9ca3af;" />
                                    <span>{{ $company->followers_count }} {{ $company->followers_count === 1 ? 'seguidor' : 'seguidores' }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Botón --}}
                        <button
                            wire:click="requestSupplier({{ $company->id }}, null)"
                            type="button"
                            style="display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: white; background-color: #0ea5e9; border-radius: 0.5rem; border: none; cursor: pointer; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#0284c7'"
                            onmouseout="this.style.backgroundColor='#0ea5e9'"
                        >
                            <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" style="width: 1rem; height: 1rem;" />
                            Solicitar como Proveedor
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
