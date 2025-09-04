<div class="paper-calculator-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">📐 Calculadora de Cortes</span>
                </div>
            </x-slot>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Ancho papel (cm)</label>
                        <input type="number" value="70" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Largo papel (cm)</label>
                        <input type="number" value="100" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Ancho corte (cm)</label>
                        <input type="number" value="9" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Largo corte (cm)</label>
                        <input type="number" value="5" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="text-xs font-medium text-gray-700 block mb-1">Cantidad deseada (uds)</label>
                    <input type="number" value="1000" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex space-x-2">
                    <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-2 px-3 rounded transition-colors">
                        🔄 Calcular
                    </button>
                    <button class="flex-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-2 px-3 rounded transition-colors">
                        📏 Vista Previa
                    </button>
                    <button class="flex-1 bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium py-2 px-3 rounded transition-colors">
                        🗂️ Guardar
                    </button>
                </div>
                
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-2">Vista previa del corte:</p>
                    <div class="bg-white border rounded p-3 h-24 flex items-center justify-center">
                        <canvas id="previewCanvas" width="200" height="80" class="border rounded"></canvas>
                    </div>
                    <div class="mt-2 text-xs text-gray-600">
                        <p><strong>Resultado:</strong> 140 cortes (14×10) - Eficiencia: 87.5%</p>
                        <p><strong>Orientación:</strong> Horizontal recomendada</p>
                    </div>
                </div>
                
                <button class="w-full text-center py-2 text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">
                    🧮 Limpiar
                </button>
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>

    <style>
    /* Custom styles for the calculator widget */
    .paper-calculator-widget input:focus,
    .paper-calculator-widget select:focus {
        outline: none;
        ring: 2px;
        ring-color: #3b82f6;
        border-color: #3b82f6;
    }

    .paper-calculator-widget canvas {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .calculation-result {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Hover effects for buttons */
    .paper-calculator-widget button:hover {
        transform: scale(1.01);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize canvas preview
        const canvas = document.getElementById('previewCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            // Draw paper background
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(10, 10, 180, 60);
            
            // Draw paper border
            ctx.strokeStyle = '#6b7280';
            ctx.lineWidth = 1;
            ctx.strokeRect(10, 10, 180, 60);
            
            // Draw cutting grid (example)
            ctx.fillStyle = 'rgba(59, 130, 246, 0.3)';
            ctx.strokeStyle = '#3b82f6';
            
            // Draw 14x10 grid example
            const itemWidth = 180 / 14;
            const itemHeight = 60 / 10;
            
            for (let i = 0; i < 14; i++) {
                for (let j = 0; j < 10; j++) {
                    const x = 10 + (i * itemWidth);
                    const y = 10 + (j * itemHeight);
                    
                    ctx.fillRect(x, y, itemWidth - 1, itemHeight - 1);
                    ctx.strokeRect(x, y, itemWidth - 1, itemHeight - 1);
                }
            }
            
            // Add labels
            ctx.fillStyle = '#374151';
            ctx.font = '8px system-ui';
            ctx.textAlign = 'center';
            ctx.fillText('70cm', 100, 78);
            ctx.fillText('100cm', 8, 40);
        }
    });

    // Calculator functionality
    function calculateCuts() {
        alert('Cálculo: 140 cortes en orientación horizontal (14×10) con 87.5% de eficiencia');
    }
    
    function previewCuts() {
        alert('Vista previa actualizada en el canvas');
    }
    
    function saveCuts() {
        alert('Cálculo guardado en favoritos');
    }
    
    function clearCalculator() {
        // Reset form values
        document.querySelectorAll('.paper-calculator-widget input[type="number"]').forEach(input => {
            input.value = '';
        });
    }
    </script>
</div>