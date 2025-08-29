<div 
    x-data="{ 
        showCanvas: @entangle('showResults'),
        calculation: @entangle('calculation')
    }"
    class="fi-wi-paper-calculator"
>
    <x-filament-widgets::widget>
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calculator class="h-5 w-5 text-primary-500" />
                    <span class="font-semibold text-gray-900 dark:text-white">Calculadora de Papel</span>
                </div>
            </x-slot>
            
            <div class="space-y-4">
                <!-- Paper Size Selection -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tamaño del Papel
                    </label>
                    
                    <select 
                        wire:model.live="paperSize" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                    >
                        @foreach($paperSizes as $key => $size)
                            <option value="{{ $key }}">{{ $size['label'] }}</option>
                        @endforeach
                    </select>
                    
                    <!-- Custom paper size inputs -->
                    @if($paperSize === 'custom')
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">Ancho (cm)</label>
                                <input 
                                    type="number" 
                                    step="0.1" 
                                    wire:model.live="customPaperWidth"
                                    class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                                    placeholder="21.6"
                                >
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">Alto (cm)</label>
                                <input 
                                    type="number" 
                                    step="0.1" 
                                    wire:model.live="customPaperHeight"
                                    class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                                    placeholder="27.9"
                                >
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Quick Paper Selection -->
                @if($availablePapers->count() > 0)
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Papeles Disponibles
                        </label>
                        
                        <div class="grid grid-cols-1 gap-1 max-h-32 overflow-y-auto">
                            @foreach($availablePapers->take(4) as $paper)
                                <button 
                                    wire:click="selectPredefinedPaper({{ $paper->id }})"
                                    class="text-left p-2 text-xs bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded border transition-colors"
                                >
                                    <span class="font-medium">{{ $paper->name }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        ({{ $paper->width }}×{{ $paper->height }} cm - {{ $paper->weight }}g)
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Item Dimensions -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Dimensiones del Item
                    </label>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">Ancho (cm)</label>
                            <input 
                                type="number" 
                                step="0.1" 
                                wire:model="itemWidth"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                                placeholder="10.5"
                            >
                            @error('itemWidth') 
                                <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">Alto (cm)</label>
                            <input 
                                type="number" 
                                step="0.1" 
                                wire:model="itemHeight"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                                placeholder="7.0"
                            >
                            @error('itemHeight') 
                                <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <!-- Calculation Buttons -->
                <div class="flex gap-2">
                    <x-filament::button
                        color="primary"
                        size="sm"
                        icon="heroicon-o-calculator"
                        wire:click="calculate"
                        class="flex-1"
                    >
                        Calcular
                    </x-filament::button>
                    
                    <x-filament::button
                        color="secondary"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                        wire:click="resetCalculator"
                    >
                        Reset
                    </x-filament::button>
                </div>
                
                <!-- Results -->
                @if($showResults && $calculation)
                    <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <!-- Best Result Summary -->
                        <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-3">
                            <h4 class="text-sm font-semibold text-primary-800 dark:text-primary-200 mb-2">
                                🏆 Mejor Aprovechamiento ({{ $calculation['best']['orientation_label'] }})
                            </h4>
                            
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-primary-600 dark:text-primary-400">Cortes:</span>
                                    <span class="font-bold text-primary-800 dark:text-primary-200">
                                        {{ $calculation['best']['total_cuts'] }}
                                    </span>
                                    <span class="text-xs text-primary-600 dark:text-primary-400">
                                        ({{ $calculation['best']['cuts_h'] }}×{{ $calculation['best']['cuts_v'] }})
                                    </span>
                                </div>
                                
                                <div>
                                    <span class="text-primary-600 dark:text-primary-400">Eficiencia:</span>
                                    <span class="font-bold text-primary-800 dark:text-primary-200">
                                        {{ number_format($calculation['best']['efficiency'], 1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Visual Canvas -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <canvas 
                                id="cuttingCanvas" 
                                width="280" 
                                height="200" 
                                class="w-full border rounded"
                                x-show="showCanvas"
                            ></canvas>
                        </div>
                        
                        <!-- Detailed Results -->
                        <div class="grid grid-cols-2 gap-2">
                            <!-- Horizontal Orientation -->
                            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                                <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    📐 Horizontal
                                </h5>
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Cortes:</span>
                                        <span class="font-semibold">{{ $calculation['horizontal']['total_cuts'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Eficiencia:</span>
                                        <span class="font-semibold">{{ number_format($calculation['horizontal']['efficiency'], 1) }}%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vertical Orientation -->
                            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                                <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    📐 Vertical
                                </h5>
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Cortes:</span>
                                        <span class="font-semibold">{{ $calculation['vertical']['total_cuts'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Eficiencia:</span>
                                        <span class="font-semibold">{{ number_format($calculation['vertical']['efficiency'], 1) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Paper Info -->
                        <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <div class="flex justify-between">
                                <span>Papel:</span>
                                <span>{{ $calculation['paper_size']['width'] }} × {{ $calculation['paper_size']['height'] }} cm</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Item:</span>
                                <span>{{ $calculation['item_size']['width'] }} × {{ $calculation['item_size']['height'] }} cm</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Desperdicio:</span>
                                <span>{{ number_format($calculation['best']['waste_area'], 1) }} cm²</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </x-filament-widgets::widget>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Draw cutting visualization when results are available
    window.addEventListener('livewire:update', function() {
        const canvas = document.getElementById('cuttingCanvas');
        if (canvas && @js($showResults) && @js($calculation)) {
            drawCuttingVisualization(canvas, @js($calculation));
        }
    });
});

function drawCuttingVisualization(canvas, calculation) {
    const ctx = canvas.getContext('2d');
    const canvasWidth = 280;
    const canvasHeight = 200;
    
    // Clear canvas
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
    
    // Calculate scale
    const paperWidth = calculation.paper_size.width;
    const paperHeight = calculation.paper_size.height;
    const maxDimension = Math.max(paperWidth, paperHeight);
    const scale = Math.min(canvasWidth - 40, canvasHeight - 40) / maxDimension;
    
    const scaledPaperWidth = paperWidth * scale;
    const scaledPaperHeight = paperHeight * scale;
    
    // Center the drawing
    const offsetX = (canvasWidth - scaledPaperWidth) / 2;
    const offsetY = (canvasHeight - scaledPaperHeight) / 2;
    
    // Draw paper background
    ctx.fillStyle = '#f8f9fa';
    ctx.fillRect(offsetX, offsetY, scaledPaperWidth, scaledPaperHeight);
    
    // Draw paper border
    ctx.strokeStyle = '#6b7280';
    ctx.lineWidth = 2;
    ctx.strokeRect(offsetX, offsetY, scaledPaperWidth, scaledPaperHeight);
    
    // Draw cutting grid
    const best = calculation.best;
    const itemWidth = calculation.item_size.width;
    const itemHeight = calculation.item_size.height;
    
    // Adjust dimensions based on orientation
    let drawItemWidth, drawItemHeight;
    if (best.orientation === 'vertical') {
        drawItemWidth = itemHeight * scale;  // Rotated
        drawItemHeight = itemWidth * scale;   // Rotated
    } else {
        drawItemWidth = itemWidth * scale;
        drawItemHeight = itemHeight * scale;
    }
    
    // Draw items
    ctx.fillStyle = 'rgba(59, 130, 246, 0.3)'; // Blue with transparency
    ctx.strokeStyle = '#3b82f6';
    ctx.lineWidth = 1;
    
    for (let i = 0; i < best.cuts_h; i++) {
        for (let j = 0; j < best.cuts_v; j++) {
            const x = offsetX + (i * drawItemWidth);
            const y = offsetY + (j * drawItemHeight);
            
            // Only draw if item fits within paper bounds
            if (x + drawItemWidth <= offsetX + scaledPaperWidth && 
                y + drawItemHeight <= offsetY + scaledPaperHeight) {
                
                ctx.fillRect(x, y, drawItemWidth, drawItemHeight);
                ctx.strokeRect(x, y, drawItemWidth, drawItemHeight);
            }
        }
    }
    
    // Draw dimensions labels
    ctx.fillStyle = '#374151';
    ctx.font = '10px system-ui';
    ctx.textAlign = 'center';
    
    // Paper dimensions
    ctx.fillText(
        `${paperWidth} cm`, 
        offsetX + scaledPaperWidth / 2, 
        offsetY + scaledPaperHeight + 15
    );
    
    // Rotate context for vertical text
    ctx.save();
    ctx.translate(offsetX - 15, offsetY + scaledPaperHeight / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.fillText(`${paperHeight} cm`, 0, 0);
    ctx.restore();
    
    // Item count label
    ctx.fillStyle = '#059669';
    ctx.font = 'bold 12px system-ui';
    ctx.textAlign = 'right';
    ctx.fillText(
        `${best.total_cuts} items`, 
        canvasWidth - 10, 
        20
    );
}
</script>

<style>
/* Custom styles for the calculator widget */
.fi-wi-paper-calculator input:focus,
.fi-wi-paper-calculator select:focus {
    @apply ring-2 ring-primary-500 border-primary-500;
}

.fi-wi-paper-calculator canvas {
    @apply bg-gray-50 dark:bg-gray-900;
    border: 1px solid rgb(229 231 235);
}

.fi-wi-paper-calculator .calculation-result {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Hover effects for paper selection buttons */
.fi-wi-paper-calculator button:hover {
    @apply transform scale-101 shadow-md;
    transition: all 0.2s ease-in-out;
}
</style>