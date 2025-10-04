<div class="paper-calculator-widget">
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-500/10">
                        <x-filament::icon icon="heroicon-o-calculator" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Calculadora de Cortes</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Optimización de papel</p>
                    </div>
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

                <div class="grid grid-cols-2 gap-3">
                    <x-filament::button
                        wire:click="calculate"
                        color="primary"
                        size="sm"
                        icon="heroicon-m-calculator"
                        class="justify-center"
                    >
                        Calcular
                    </x-filament::button>
                    <x-filament::button
                        wire:click="resetCalculator"
                        color="gray"
                        size="sm"
                        outlined
                        icon="heroicon-m-arrow-path"
                        class="justify-center"
                    >
                        Limpiar
                    </x-filament::button>
                </div>

                @if($showResults && $calculation)
                    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-scissors" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Resultado del Cálculo</h4>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4 flex items-center justify-center h-32">
                                <canvas
                                    id="previewCanvas"
                                    width="240"
                                    height="100"
                                    class="border rounded dark:border-gray-600"
                                    data-paper-width="{{ $calculation['paper_size']['width'] }}"
                                    data-paper-height="{{ $calculation['paper_size']['height'] }}"
                                    data-item-width="{{ $calculation['item_size']['width'] }}"
                                    data-item-height="{{ $calculation['item_size']['height'] }}"
                                    data-cuts-h="{{ $calculation['best']['cuts_h'] }}"
                                    data-cuts-v="{{ $calculation['best']['cuts_v'] }}"
                                ></canvas>
                            </div>

                            <!-- Results Grid -->
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="text-center p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900">
                                    <div class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-1">Horizontal</div>
                                    <div class="text-sm font-bold text-blue-700 dark:text-blue-300">{{ $calculation['horizontal']['total_cuts'] }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400">{{ number_format($calculation['horizontal']['efficiency'], 1) }}%</div>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900">
                                    <div class="text-xs font-semibold text-green-900 dark:text-green-100 mb-1">Vertical</div>
                                    <div class="text-sm font-bold text-green-700 dark:text-green-300">{{ $calculation['vertical']['total_cuts'] }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-400">{{ number_format($calculation['vertical']['efficiency'], 1) }}%</div>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900">
                                    <div class="text-xs font-semibold text-purple-900 dark:text-purple-100 mb-1">Máximo</div>
                                    <div class="text-sm font-bold text-purple-700 dark:text-purple-300">{{ $calculation['maximum']['total_cuts'] }}</div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400">{{ number_format($calculation['maximum']['efficiency'], 1) }}%</div>
                                </div>
                            </div>

                            <!-- Best Result Summary -->
                            <div class="space-y-2 p-3 rounded-lg bg-primary-50 dark:bg-primary-950/20 border border-primary-200 dark:border-primary-900">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Mejor resultado:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ $calculation['best']['total_cuts'] }} cortes ({{ $calculation['best']['cuts_h'] }}×{{ $calculation['best']['cuts_v'] }})
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Eficiencia:</span>
                                    <x-filament::badge color="success" size="sm">
                                        {{ number_format($calculation['best']['efficiency'], 1) }}%
                                    </x-filament::badge>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Desperdicio:</span>
                                    <x-filament::badge color="warning" size="sm">
                                        {{ number_format($calculation['best']['waste'], 1) }}%
                                    </x-filament::badge>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Orientación:</span>
                                    <span class="font-medium text-primary-700 dark:text-primary-300">
                                        {{ $calculation['best']['orientation_label'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>

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