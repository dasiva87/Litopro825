<div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <!-- Header con icono verde -->
    <div style="display: flex; align-items: center; margin-bottom: 24px;">
        <div style="width: 24px; height: 24px; background: #22c55e; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
            <svg style="width: 16px; height: 16px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
        </div>
        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Calculadora de Cortes</h3>
    </div>

    <!-- Formulario -->
    <form wire:submit="calcular">
        <!-- Fila 1: Ancho y Largo del papel -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Ancho papel (cm)</label>
                <input
                    type="number"
                    wire:model="anchoPapel"
                    value="70"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Largo papel (cm)</label>
                <input
                    type="number"
                    wire:model="largoPapel"
                    value="100"
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
                    wire:model="anchoCorte"
                    value="10"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Largo corte (cm)</label>
                <input
                    type="number"
                    wire:model="largoCorte"
                    value="15"
                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
                >
            </div>
        </div>

        <!-- Cantidad deseada -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Cantidad deseada (piezas)</label>
            <input
                type="number"
                wire:model="cantidadDeseada"
                value="1000"
                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white;"
            >
        </div>

        <!-- Botones de orientación -->
        <div style="display: flex; gap: 12px; margin-bottom: 20px;">
            <button
                type="button"
                wire:click="setOrientacion('vertical')"
                style="flex: 1; padding: 14px 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap;"
            >
                <span style="margin-right: 4px;">⚡</span>
                Vertical
            </button>
            <button
                type="button"
                wire:click="setOrientacion('horizontal')"
                style="flex: 1; padding: 14px 12px; background: #22c55e; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap;"
            >
                <span style="margin-right: 4px;">↔️</span>
                Horizontal
            </button>
            <button
                type="button"
                wire:click="setOrientacion('optimo')"
                style="flex: 1; padding: 14px 12px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; min-height: 44px; white-space: nowrap;"
            >
                <span style="margin-right: 4px;">✨</span>
                Óptimo
            </button>
        </div>

        <!-- Botón calcular -->
        <button
            type="submit"
            style="width: 100%; padding: 16px; background: #374151; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 24px;"
        >
            <span style="margin-right: 8px;">✏️</span>
            Calcular
        </button>
    </form>

    <!-- Resultado -->
    <div style="padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
        <p style="font-size: 14px; color: #6b7280; margin: 0 0 8px 0; font-weight: 500;">Vista previa del corte:</p>
        <p style="font-size: 12px; color: #9ca3af; margin: 0; font-style: italic;">Ingresa las medidas y presiona calcular</p>
    </div>
</div>