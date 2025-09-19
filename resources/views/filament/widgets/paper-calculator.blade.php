<div class="paper-calculator-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 002 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">📐 Calculadora de Cortes</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                <!-- Paper Size Selection -->
                <div>
                    <label class="text-xs font-medium text-gray-700 block mb-1">Tamaño de papel</label>
                    <select wire:model.live="paperSize" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($paperSizes as $key => $size)
                            <option value="{{ $key }}">{{ $size['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if($paperSize === 'custom')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-700 block mb-1">Ancho papel (cm)</label>
                            <input type="number" wire:model.live="customPaperWidth" step="0.1" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-700 block mb-1">Alto papel (cm)</label>
                            <input type="number" wire:model.live="customPaperHeight" step="0.1" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                @endif

                <!-- Available Papers from Inventory -->
                @if($availablePapers->isNotEmpty())
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">O selecciona del inventario:</label>
                        <select wire:change="selectPredefinedPaper($event.target.value)" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Seleccionar papel --</option>
                            @foreach($availablePapers as $paper)
                                <option value="{{ $paper->id }}">{{ $paper->name }} ({{ $paper->width }}x{{ $paper->height }}cm)</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Ancho corte (cm)</label>
                        <input type="number" wire:model="itemWidth" step="0.1" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('itemWidth') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 block mb-1">Alto corte (cm)</label>
                        <input type="number" wire:model="itemHeight" step="0.1" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('itemHeight') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex space-x-2">
                    <button wire:click="calculate" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-2 px-3 rounded transition-colors">
                        🔄 Calcular
                    </button>
                    <button wire:click="resetCalculator" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium py-2 px-3 rounded transition-colors">
                        🧮 Limpiar
                    </button>
                </div>

                @if($showResults && $calculation)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg calculation-result">
                        <p class="text-xs text-gray-600 mb-2">Vista previa del corte:</p>
                        <div class="bg-white border rounded p-3 h-32 flex items-center justify-center">
                            <canvas
                                id="previewCanvas"
                                width="240"
                                height="100"
                                class="border rounded"
                                data-paper-width="{{ $calculation['paper_size']['width'] }}"
                                data-paper-height="{{ $calculation['paper_size']['height'] }}"
                                data-item-width="{{ $calculation['item_size']['width'] }}"
                                data-item-height="{{ $calculation['item_size']['height'] }}"
                                data-cuts-h="{{ $calculation['best']['cuts_h'] }}"
                                data-cuts-v="{{ $calculation['best']['cuts_v'] }}"
                            ></canvas>
                        </div>

                        <!-- Results Tabs -->
                        <div class="mt-3">
                            <div class="grid grid-cols-3 gap-1 text-xs">
                                <div class="text-center p-2 bg-blue-100 rounded">
                                    <div class="font-semibold">Horizontal</div>
                                    <div>{{ $calculation['horizontal']['total_cuts'] }} cortes</div>
                                    <div>{{ number_format($calculation['horizontal']['efficiency'], 1) }}%</div>
                                </div>
                                <div class="text-center p-2 bg-green-100 rounded">
                                    <div class="font-semibold">Vertical</div>
                                    <div>{{ $calculation['vertical']['total_cuts'] }} cortes</div>
                                    <div>{{ number_format($calculation['vertical']['efficiency'], 1) }}%</div>
                                </div>
                                <div class="text-center p-2 bg-purple-100 rounded">
                                    <div class="font-semibold">Máximo</div>
                                    <div>{{ $calculation['maximum']['total_cuts'] }} cortes</div>
                                    <div>{{ number_format($calculation['maximum']['efficiency'], 1) }}%</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 text-xs text-gray-600">
                            <p><strong>Mejor resultado:</strong> {{ $calculation['best']['total_cuts'] }} cortes ({{ $calculation['best']['cuts_h'] }}×{{ $calculation['best']['cuts_v'] }}) - Eficiencia: {{ number_format($calculation['best']['efficiency'], 1) }}%</p>
                            <p><strong>Orientación recomendada:</strong> {{ $calculation['best']['orientation_label'] }}</p>
                            <p><strong>Desperdicio:</strong> {{ number_format($calculation['best']['waste'], 1) }}%</p>
                        </div>
                    </div>
                @endif
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
        updateCanvas();
    });

    // Listen for Livewire updates to redraw canvas
    document.addEventListener('livewire:updated', function() {
        updateCanvas();
    });

    function updateCanvas() {
        const canvas = document.getElementById('previewCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;

        // Clear canvas
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);

        // Get data from canvas attributes
        const paperWidth = parseFloat(canvas.dataset.paperWidth) || 100;
        const paperHeight = parseFloat(canvas.dataset.paperHeight) || 70;
        const itemWidth = parseFloat(canvas.dataset.itemWidth) || 9;
        const itemHeight = parseFloat(canvas.dataset.itemHeight) || 5;
        const cutsH = parseInt(canvas.dataset.cutsH) || 11;
        const cutsV = parseInt(canvas.dataset.cutsV) || 14;

        // Calculate scale to fit paper in canvas with padding
        const padding = 20;
        const availableWidth = canvasWidth - (padding * 2);
        const availableHeight = canvasHeight - (padding * 2);

        const scaleX = availableWidth / paperWidth;
        const scaleY = availableHeight / paperHeight;
        const scale = Math.min(scaleX, scaleY);

        const scaledPaperWidth = paperWidth * scale;
        const scaledPaperHeight = paperHeight * scale;

        // Center the paper in canvas
        const offsetX = (canvasWidth - scaledPaperWidth) / 2;
        const offsetY = (canvasHeight - scaledPaperHeight) / 2;

        // Draw paper background
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(offsetX, offsetY, scaledPaperWidth, scaledPaperHeight);

        // Draw paper border
        ctx.strokeStyle = '#6b7280';
        ctx.lineWidth = 2;
        ctx.strokeRect(offsetX, offsetY, scaledPaperWidth, scaledPaperHeight);

        // Draw cutting grid only if we have valid data
        if (cutsH > 0 && cutsV > 0) {
            ctx.fillStyle = 'rgba(59, 130, 246, 0.2)';
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 1;

            const cellWidth = scaledPaperWidth / cutsH;
            const cellHeight = scaledPaperHeight / cutsV;

            for (let i = 0; i < cutsH; i++) {
                for (let j = 0; j < cutsV; j++) {
                    const x = offsetX + (i * cellWidth);
                    const y = offsetY + (j * cellHeight);

                    ctx.fillRect(x + 1, y + 1, cellWidth - 2, cellHeight - 2);
                    ctx.strokeRect(x, y, cellWidth, cellHeight);
                }
            }
        }

        // Add dimension labels
        ctx.fillStyle = '#374151';
        ctx.font = '10px system-ui';
        ctx.textAlign = 'center';

        // Paper width label (bottom)
        ctx.fillText(`${paperWidth}cm`, offsetX + scaledPaperWidth / 2, offsetY + scaledPaperHeight + 15);

        // Paper height label (left, rotated)
        ctx.save();
        ctx.translate(offsetX - 15, offsetY + scaledPaperHeight / 2);
        ctx.rotate(-Math.PI / 2);
        ctx.fillText(`${paperHeight}cm`, 0, 0);
        ctx.restore();

        // Item dimensions label (if cuts exist)
        if (cutsH > 0 && cutsV > 0) {
            ctx.fillStyle = '#1f2937';
            ctx.font = '8px system-ui';
            ctx.textAlign = 'center';
            ctx.fillText(`${itemWidth}×${itemHeight}cm`, offsetX + scaledPaperWidth / 2, offsetY - 5);
        }
    }
    </script>
</div>