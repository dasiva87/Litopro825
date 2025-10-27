<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Calculadora de Cortes
        </x-slot>

    <!-- Formulario -->
    <form wire:submit="calcular">
        <!-- Fila 1: Ancho y Largo del papel -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Ancho papel (cm)</label>
                <input
                    type="number"
                    wire:model.live="anchoPapel"
                    min="1"
                    step="0.1"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Largo papel (cm)</label>
                <input
                    type="number"
                    wire:model.live="largoPapel"
                    min="1"
                    step="0.1"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
        </div>

        <!-- Fila 2: Ancho y Largo del corte -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Ancho corte (cm)</label>
                <input
                    type="number"
                    wire:model.live="anchoCorte"
                    min="0.1"
                    step="0.1"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Largo corte (cm)</label>
                <input
                    type="number"
                    wire:model.live="largoCorte"
                    min="0.1"
                    step="0.1"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
        </div>

        <!-- Cantidad deseada -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Cantidad deseada (piezas)</label>
            <input
                type="number"
                wire:model.live="cantidadDeseada"
                min="1"
                step="1"
                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
            >
        </div>

        <!-- Botones de orientación -->
        <div style="display: flex; gap: 12px; margin-bottom: 20px;">
            <button
                type="button"
                wire:click="setOrientacion('vertical')"
                style="flex: 1; padding: 14px 12px; background: {{ $orientacion === 'vertical' ? '#1e40af' : '#3b82f6' }}; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap; transition: background-color 0.2s;"
            >
                Vertical
            </button>
            <button
                type="button"
                wire:click="setOrientacion('horizontal')"
                style="flex: 1; padding: 14px 12px; background: {{ $orientacion === 'horizontal' ? '#15803d' : '#22c55e' }}; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap; transition: background-color 0.2s;"
            >
                Horizontal
            </button>
            <button
                type="button"
                wire:click="setOrientacion('optimo')"
                style="flex: 1; padding: 14px 12px; background: {{ $orientacion === 'optimo' ? '#c2410c' : '#f97316' }}; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap; transition: background-color 0.2s;"
            >
                Óptimo
            </button>
        </div>

        <!-- Botón reset -->
        <div style="text-align: center; margin-bottom: 20px;">
            <button
                type="button"
                wire:click="resetCalculator"
                style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"
            >
                Valores por defecto
            </button>
        </div>

    </form>

    <!-- Resultado -->
    @if($calculado && $resultado)
        @if(isset($resultado['error']))
            <!-- Error -->
            <div style="padding: 20px; margin-top: 24px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                        <span style="color: white; font-size: 12px; font-weight: bold;">!</span>
                    </div>
                    <p style="color: #dc2626; margin: 0; font-weight: 500;">{{ $resultado['error'] }}</p>
                </div>
            </div>
        @else
            <!-- Resultados exitosos -->
                <!-- Header con orientación -->
                <div style="display: flex; align-items: center; margin-bottom: 16px;">
                    <div style="width: 24px; height: 24px; background: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                        <span style="color: white; font-size: 12px; font-weight: bold;">✓</span>
                    </div>
                    <h4 style="color: #166534; margin: 0; font-size: 16px; font-weight: 600;">
                        {{ ucfirst($resultado['orientacion']) }}
                    </h4>
                </div>

                <!-- Métricas principales -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div style="background: white; padding: 10px; border-radius: 8px; border: 1px solid #d1fae5;">
                        <p style="color: #6b7280; font-size: 12px; margin: 0 0 4px 0; font-weight: 600;">Piezas por pliego</p>
                        <p style="color: #1f2937; font-size: 24px; font-weight: bold; margin: 0;">{{ $resultado['piezasPorHoja'] }}</p>
                    </div>

                    <div style="background: white; padding: 10px; border-radius: 8px; border: 1px solid #d1fae5;">
                        <p style="color: #6b7280; font-size: 12px; margin: 0 0 4px 0; font-weight: 600;">Pliegos necesarios</p>
                        <p style="color: #1f2937; font-size: 24px; font-weight: bold; margin: 0;">{{ $resultado['hojasNecesarias'] }}</p>
                    </div>

                    <div style="background: white; padding: 10px; border-radius: 8px; border: 1px solid #d1fae5;">
                        <p style="color: #6b7280; font-size: 12px; margin: 0 0 4px 0; font-weight: 600;">Eficiencia</p>
                        <p style="color: #059669; font-size: 24px; font-weight: bold; margin: 0;">{{ $resultado['eficiencia'] }}%</p>
                    </div>
                </div>

                <!-- Información adicional -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 16px; background: #fefefe; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div>
                        <p style="color: #374151; font-size: 13px; margin: 0 0 8px 0;"><strong>Piezas obtenidas:</strong> {{ $resultado['piezasObtenidas'] }}</p>
                        <p style="color: #374151; font-size: 13px; margin: 0;"><strong>Piezas sobrantes:</strong> {{ $resultado['piezasSobrantes'] }}</p>
                    </div>
                    <div>
                        <p style="color: #374151; font-size: 13px; margin: 0 0 8px 0;"><strong>Área útil:</strong> {{ $resultado['areaUtil'] }} cm²</p>
                        <p style="color: #374151; font-size: 13px; margin: 0;"><strong>Desperdicio:</strong> {{ $resultado['desperdicioArea'] }} cm²</p>
                    </div>
                </div>

                <!-- Visualización con SVG -->
                <div style="margin-top: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 16px;">
                    <h5 style="color: #374151; margin: 0 0 12px 0; font-size: 14px; font-weight: 600;">Distribución visual del corte:</h5>
                    <div style="display: flex; justify-content: center; align-items: center; background: #f9fafb; border-radius: 6px; padding: 20px; min-height: 300px;">
                        {!! $this->generateCuttingSVG() !!}
                    </div>
                    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 12px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 16px; height: 16px; background: #3b82f6; border-radius: 2px;"></div>
                            <span style="color: #6b7280; font-size: 12px;">Papel ({{ $anchoPapel }}×{{ $largoPapel }}cm)</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 16px; height: 16px; background: #22c55e; border-radius: 2px;"></div>
                            <span style="color: #6b7280; font-size: 12px;">Piezas ({{ $anchoCorte }}×{{ $largoCorte }}cm)</span>
                        </div>
                    </div>
                </div>
            
        @endif
    @else
        <!-- Estado inicial -->
        <div style="padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; font-weight: 500;">Vista previa del corte:</p>
            <p style="font-size: 12px; color: #9ca3af; margin: 0; font-style: italic;">Los valores se calculan automáticamente</p>
        </div>
    @endif
    </x-filament::section>
</x-filament-widgets::widget>