<div style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
    {{-- Modal de confirmación de duplicado --}}
    @if($showDuplicateConfirmation)
        <div style="position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
            <div style="background: white; border-radius: 1rem; padding: 1.5rem; max-width: 28rem; width: 90%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                {{-- Icono de advertencia --}}
                <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                    <div style="width: 4rem; height: 4rem; border-radius: 50%; background-color: #fef3c7; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 2rem; height: 2rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>

                {{-- Título --}}
                <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; text-align: center; margin-bottom: 0.5rem;">
                    Contacto existente encontrado
                </h3>

                {{-- Mensaje --}}
                <p style="font-size: 0.875rem; color: #6b7280; text-align: center; margin-bottom: 1rem;">
                    Ya tienes registrado un proveedor local con el mismo número de documento:
                </p>

                {{-- Info del contacto existente --}}
                <div style="background-color: #f3f4f6; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: linear-gradient(to bottom right, #6b7280, #4b5563); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1rem;">
                            {{ strtoupper(substr($duplicateContactName ?? '', 0, 1)) }}
                        </div>
                        <div>
                            <p style="font-weight: 600; color: #111827; font-size: 0.875rem;">{{ $duplicateContactName }}</p>
                            <p style="font-size: 0.75rem; color: #6b7280;">Proveedor local existente</p>
                        </div>
                    </div>
                </div>

                {{-- Explicación --}}
                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1.5rem;">
                    <p style="font-size: 0.75rem; color: #1e40af;">
                        <strong>Si continúas:</strong> Cuando <strong>{{ $targetCompanyName }}</strong> apruebe tu solicitud, este contacto local se vinculará automáticamente con la empresa del gremio y sus datos se actualizarán.
                    </p>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 0.75rem;">
                    <button
                        wire:click="cancelDuplicateRequest"
                        type="button"
                        style="flex: 1; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; color: #374151; background-color: white; border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer;"
                    >
                        Cancelar
                    </button>
                    <button
                        wire:click="confirmDuplicateRequest"
                        type="button"
                        style="flex: 1; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; color: white; background-color: #0ea5e9; border: none; border-radius: 0.5rem; cursor: pointer;"
                    >
                        Continuar y Vincular
                    </button>
                </div>
            </div>
        </div>
    @endif

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
        {{-- Grid de empresas: auto-fill para 4 columnas en desktop --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; max-height: 550px; overflow-y: auto; padding: 0.5rem; width: 100%;">
            @foreach($companies as $company)
                <div style="position: relative; border-radius: 0.75rem; border: 2px solid #e5e7eb; background-color: white; overflow: hidden; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"
                     onmouseover="this.style.borderColor='#7dd3fc'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';"
                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)';">
                    {{-- Header con Avatar y Nombre --}}
                    <div style="background: linear-gradient(to right, #f0f9ff, white); padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            {{-- Avatar --}}
                            @if($company->logo)
                                <img src="{{ Storage::url($company->logo) }}"
                                     alt="{{ $company->name }}"
                                     style="height: 3rem; width: 3rem; border-radius: 0.5rem; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex-shrink: 0;">
                            @else
                                <div style="height: 3rem; width: 3rem; border-radius: 0.5rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.25rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex-shrink: 0;">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </div>
                            @endif
                            {{-- Nombre --}}
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-weight: 600; font-size: 0.9rem; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0;">
                                    {{ $company->name }}
                                </h4>
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
                                    <span style="display: inline-block; margin-top: 0.25rem; padding: 0.125rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 1rem; background-color: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['ring'] }};">
                                        {{ $typeLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Body con Info --}}
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        {{-- Metadata --}}
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #6b7280;">
                            @if($company->city || $company->state || $company->country)
                                <div style="display: flex; align-items: center; gap: 0.25rem;">
                                    <x-filament::icon icon="heroicon-m-map-pin" style="width: 0.875rem; height: 0.875rem; color: #9ca3af;" />
                                    <span>{{ $company->city?->name ?? ($company->state?->name ?? $company->country?->name) }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Botón o Badge de Pendiente --}}
                        @if(in_array($company->id, $pendingRequestIds))
                            {{-- Badge de solicitud pendiente --}}
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.375rem; width: 100%; padding: 0.625rem 1rem; font-size: 0.8rem; font-weight: 600; color: #92400e; background-color: #fef3c7; border-radius: 0.5rem; border: 1px solid #fcd34d;">
                                <x-filament::icon icon="heroicon-m-clock" style="width: 1rem; height: 1rem;" />
                                Solicitud Pendiente
                            </div>
                        @else
                            {{-- Botón para solicitar --}}
                            <button
                                wire:click="requestSupplier({{ $company->id }}, null)"
                                type="button"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; width: 100%; padding: 0.625rem 1rem; font-size: 0.8rem; font-weight: 600; color: white; background: linear-gradient(135deg, #0ea5e9, #0284c7); border-radius: 0.5rem; border: none; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.3);"
                                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(14, 165, 233, 0.4)';"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(14, 165, 233, 0.3)';"
                            >
                                <x-filament::icon icon="heroicon-m-paper-airplane" style="width: 1rem; height: 1rem;" />
                                Solicitar Proveedor
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
