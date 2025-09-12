@php
    $itemable = $record->itemable;
    $itemType = class_basename($record->itemable_type);
@endphp

<div class="p-6 space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Información General --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">📋 Información General</h3>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tipo</dt>
                        <dd class="text-sm text-gray-900">{{ $itemType }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cantidad</dt>
                        <dd class="text-sm text-gray-900">{{ number_format($record->quantity) }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Descripción</dt>
                        <dd class="text-sm text-gray-900">{{ $itemable?->description ?? 'Sin descripción' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Detalles Específicos por Tipo --}}
            @if($itemable)
                @switch($itemType)
                    @case('SimpleItem')
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">📐 Especificaciones Técnicas</h3>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-blue-600">Dimensiones</dt>
                                    <dd class="text-sm text-blue-900">{{ $itemable->horizontal_size }}cm × {{ $itemable->vertical_size }}cm</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-blue-600">Papel</dt>
                                    <dd class="text-sm text-blue-900">{{ $itemable->paper?->name ?? 'No especificado' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-blue-600">Máquina</dt>
                                    <dd class="text-sm text-blue-900">{{ $itemable->printingMachine?->name ?? 'No especificada' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-blue-600">Tintas</dt>
                                    <dd class="text-sm text-blue-900">{{ $itemable->ink_front_count }}/{{ $itemable->ink_back_count }}</dd>
                                </div>
                            </dl>
                        </div>
                        @break

                    @case('MagazineItem')
                        <div class="bg-green-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-green-900 mb-2">📖 Detalles de Revista</h3>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-green-600">Dimensiones Cerrado</dt>
                                    <dd class="text-sm text-green-900">{{ $itemable->closed_width }}cm × {{ $itemable->closed_height }}cm</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-green-600">Encuadernación</dt>
                                    <dd class="text-sm text-green-900">{{ ucfirst($itemable->binding_type) }} - {{ ucfirst($itemable->binding_side) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-green-600">Total Páginas</dt>
                                    <dd class="text-sm text-green-900">{{ $itemable->pages->sum('page_quantity') }} páginas</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-green-600">Tipos de Página</dt>
                                    <dd class="text-sm text-green-900">{{ $itemable->pages->pluck('page_type')->unique()->implode(', ') }}</dd>
                                </div>
                            </dl>
                        </div>
                        @break

                    @case('TalonarioItem')
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-yellow-900 mb-2">📋 Detalles de Talonario</h3>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-yellow-600">Numeración</dt>
                                    <dd class="text-sm text-yellow-900">{{ $itemable->prefijo }}{{ $itemable->numero_inicial }} - {{ $itemable->prefijo }}{{ $itemable->numero_final }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-yellow-600">Números por Talonario</dt>
                                    <dd class="text-sm text-yellow-900">{{ $itemable->numeros_por_talonario }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-yellow-600">Total de Números</dt>
                                    <dd class="text-sm text-yellow-900">{{ ($itemable->numero_final - $itemable->numero_inicial + 1) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-yellow-600">Hojas</dt>
                                    <dd class="text-sm text-yellow-900">{{ $itemable->sheets->count() }} hojas configuradas</dd>
                                </div>
                            </dl>
                        </div>
                        @break

                    @case('DigitalItem')
                        <div class="bg-purple-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-purple-900 mb-2">💻 Servicio Digital</h3>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-purple-600">Tipo de Valoración</dt>
                                    <dd class="text-sm text-purple-900">{{ ucfirst($itemable->pricing_type) === 'Unit' ? 'Por Unidad' : 'Por Tamaño' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-purple-600">Valor Unitario</dt>
                                    <dd class="text-sm text-purple-900">${{ number_format($itemable->unit_value, 2) }}</dd>
                                </div>
                                @if($itemable->pricing_type === 'size' && $itemable->width && $itemable->height)
                                    <div>
                                        <dt class="text-sm font-medium text-purple-600">Dimensiones</dt>
                                        <dd class="text-sm text-purple-900">{{ $itemable->width }}cm × {{ $itemable->height }}cm</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                        @break

                    @case('Product')
                        <div class="bg-orange-50 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-orange-900 mb-2">📦 Producto</h3>
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-orange-600">Código</dt>
                                    <dd class="text-sm text-orange-900">{{ $itemable->code ?? 'Sin código' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-orange-600">Stock Disponible</dt>
                                    <dd class="text-sm text-orange-900">{{ $itemable->stock ?? 0 }} unidades</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-orange-600">Precio de Lista</dt>
                                    <dd class="text-sm text-orange-900">${{ number_format($itemable->price ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-orange-600">Categoría</dt>
                                    <dd class="text-sm text-orange-900">{{ $itemable->category?->name ?? 'Sin categoría' }}</dd>
                                </div>
                            </dl>
                        </div>
                        @break
                @endswitch
            @endif
        </div>

        {{-- Panel de Costos --}}
        <div class="space-y-4">
            <div class="bg-gray-900 text-white rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4">💰 Resumen de Costos</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-300">Precio Unitario</dt>
                        <dd class="font-medium">${{ number_format($record->unit_price ?? 0, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-300">Cantidad</dt>
                        <dd class="font-medium">{{ number_format($record->quantity) }}</dd>
                    </div>
                    <hr class="border-gray-700">
                    <div class="flex justify-between text-lg font-bold">
                        <dt>Total</dt>
                        <dd>${{ number_format($itemable?->final_price ?? $record->total_price ?? 0, 2) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Información Adicional --}}
            @if($itemable && ($itemable->notes || $itemable->created_at))
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">ℹ️ Información Adicional</h3>
                    @if($itemable->notes)
                        <div class="mb-3">
                            <dt class="text-xs font-medium text-gray-500 mb-1">Notas</dt>
                            <dd class="text-sm text-gray-700">{{ $itemable->notes }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-gray-500 mb-1">Creado</dt>
                        <dd class="text-sm text-gray-700">{{ $itemable->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                </div>
            @endif

            {{-- Estado --}}
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-700 mb-2">📊 Estado</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-blue-600">Calculado</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            ✓ Sí
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-blue-600">Actualizado</span>
                        <span class="text-xs text-blue-700">{{ $record->updated_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>