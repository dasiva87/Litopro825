<!-- Paper Calculator Widget - Enhanced Livewire 3 -->
<div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6" 
     x-data="paperCalculatorWidget()" 
     x-init="initCanvas()" 
     wire:ignore.self>
    <!-- Header con estilo moderno -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="p-2 bg-blue-100 rounded-xl">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Calculadora de Papel</h3>
                <p class="text-sm text-gray-500">Optimiza tus cortes</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <select wire:model.live="paperSize" class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @foreach($this->paperSizes as $key => $size)
                <option value="{{ $key }}">{{ $size['label'] }}</option>
                @endforeach
            </select>
            
            <!-- Loading indicator for paper size changes -->
            <div wire:loading wire:target="paperSize" class="text-blue-600">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- Formulario con diseño mejorado -->
    <div class="space-y-4">
        <!-- Tamaño del papel -->
        <div class="grid grid-cols-2 gap-3" @if($paperSize === 'custom') style="display: grid;" @else style="display: none;" @endif>
            <div>
                <label class="text-xs font-medium text-gray-700 block mb-1">Ancho papel (cm)</label>
                <input type="number" wire:model.live="customPaperWidth" step="0.1" min="1"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="70.0">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-700 block mb-1">Alto papel (cm)</label>
                <input type="number" wire:model.live="customPaperHeight" step="0.1" min="1"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="100.0">
            </div>
        </div>
        
        <!-- Tamaño del item -->
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <label class="text-xs font-medium text-gray-700 block mb-1">Ancho item (cm)</label>
                <input type="number" wire:model.live="itemWidth" step="0.1" min="0.1"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="9.0">
                <div wire:loading wire:target="itemWidth" class="absolute right-2 top-7">
                    <svg class="animate-spin w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            <div class="relative">
                <label class="text-xs font-medium text-gray-700 block mb-1">Alto item (cm)</label>
                <input type="number" wire:model.live="itemHeight" step="0.1" min="0.1"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="5.0">
                <div wire:loading wire:target="itemHeight" class="absolute right-2 top-7">
                    <svg class="animate-spin w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="flex space-x-2">
            <button wire:click="calculateOptimized" 
                    wire:loading.attr="disabled"
                    class="flex-1 flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-all duration-200">
                
                <!-- Normal state icon -->
                <svg wire:loading.remove wire:target="calculateOptimized" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                
                <!-- Loading state spinner -->
                <svg wire:loading wire:target="calculateOptimized" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                
                <span wire:loading.remove wire:target="calculateOptimized">Calcular</span>
                <span wire:loading wire:target="calculateOptimized">Calculando...</span>
            </button>
            
            <button wire:click="previewCutting" 
                    class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 text-sm font-medium rounded-lg transition-colors flex items-center">
                <svg wire:loading.remove wire:target="previewCutting" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg wire:loading wire:target="previewCutting" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="previewCutting">Vista</span>
                <span wire:loading wire:target="previewCutting">Cargando...</span>
            </button>
            
            <button wire:click="resetCalculator" 
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors flex items-center">
                <svg wire:loading.remove wire:target="resetCalculator" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg wire:loading wire:target="resetCalculator" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>
        
        <!-- Resultados -->
        <div x-show="{{ $showResults ? 'true' : 'false' }}" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform scale-95 translate-y-4" 
             x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="mt-4 space-y-4">
            <!-- Canvas visualización -->
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Vista previa del corte:</p>
                <div class="bg-white border border-gray-200 rounded-lg p-4 flex justify-center">
                    <canvas id="cuttingCanvas" width="280" height="160" class="border border-gray-300 rounded"></canvas>
                </div>
            </div>
            
            <!-- Métricas del resultado -->
            @if($calculation)
            <div class="grid grid-cols-2 gap-3" 
                 x-data="{ animateCards: false }"
                 x-init="setTimeout(() => animateCards = true, 100)"
                 x-intersect="animateCards = true">
                 
                <div class="bg-green-50 rounded-xl p-3 border border-green-200 transform transition-all duration-500 hover:scale-105" 
                     :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                     style="transition-delay: 0ms;">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></div>
                        <span class="text-xs font-medium text-green-800">Cortes Totales</span>
                    </div>
                    <p class="text-lg font-bold text-green-900 mt-1 animate-count-up">{{ $calculation['best']['total_cuts'] ?? 0 }}</p>
                    <p class="text-xs text-green-700">{{ ($calculation['best']['cuts_h'] ?? 0) }} × {{ ($calculation['best']['cuts_v'] ?? 0) }}</p>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-3 border border-blue-200 transform transition-all duration-500 hover:scale-105" 
                     :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                     style="transition-delay: 100ms;">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full pulse-dot"></div>
                        <span class="text-xs font-medium text-blue-800">Eficiencia</span>
                    </div>
                    <p class="text-lg font-bold text-blue-900 mt-1 animate-count-up">{{ number_format($calculation['best']['efficiency'] ?? 0, 1) }}%</p>
                    <p class="text-xs text-blue-700">{{ $calculation['best']['orientation_label'] ?? 'N/A' }}</p>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Papeles disponibles -->
        @if($this->getAvailablePapers()->count() > 0)
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-medium text-gray-700 mb-2">Papeles disponibles en inventario:</p>
            <div class="space-y-1 max-h-32 overflow-y-auto">
                @foreach($this->getAvailablePapers() as $paper)
                <button @click="selectPaper({{ $paper->id }}, '{{ $paper->name }}', {{ $paper->width }}, {{ $paper->height }})"
                        class="w-full flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg text-left text-xs transition-colors">
                    <div>
                        <span class="font-medium text-gray-900">{{ $paper->name }}</span>
                        <span class="text-gray-500 ml-1">({{ $paper->width }}×{{ $paper->height }}cm)</span>
                    </div>
                    <span class="text-gray-400 text-xs">{{ $paper->weight }}g</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Alpine.js para funcionalidad de la calculadora -->
<script>
function paperCalculatorWidget() {
    return {
        paperSize: 'carta',
        itemWidth: '',
        itemHeight: '',
        customPaperWidth: '',
        customPaperHeight: '',
        calculation: null,
        showResults: false,
        
        paperSizes: @json($this->paperSizes),
        
        get canCalculate() {
            if (this.paperSize === 'custom') {
                return this.itemWidth && this.itemHeight && this.customPaperWidth && this.customPaperHeight;
            }
            return this.itemWidth && this.itemHeight;
        },
        
        updatePaperSize() {
            this.showResults = false;
            this.calculation = null;
        },
        
        async calculate() {
            if (!this.canCalculate) return;
            
            try {
                const response = await fetch('/admin/paper-calculator/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        paperSize: this.paperSize,
                        itemWidth: parseFloat(this.itemWidth),
                        itemHeight: parseFloat(this.itemHeight),
                        customPaperWidth: this.paperSize === 'custom' ? parseFloat(this.customPaperWidth) : null,
                        customPaperHeight: this.paperSize === 'custom' ? parseFloat(this.customPaperHeight) : null
                    })
                });
                
                if (response.ok) {
                    this.calculation = await response.json();
                    this.showResults = true;
                    this.drawCuttingPreview();
                } else {
                    console.error('Error calculating cuts');
                }
            } catch (error) {
                console.error('Error:', error);
                // Fallback calculation
                this.performFallbackCalculation();
            }
        },
        
        performFallbackCalculation() {
            const paperWidth = this.paperSize === 'custom' ? parseFloat(this.customPaperWidth) : this.paperSizes[this.paperSize].width;
            const paperHeight = this.paperSize === 'custom' ? parseFloat(this.customPaperHeight) : this.paperSizes[this.paperSize].height;
            const itemW = parseFloat(this.itemWidth);
            const itemH = parseFloat(this.itemHeight);
            
            const cutsH = Math.floor(paperWidth / itemW);
            const cutsV = Math.floor(paperHeight / itemH);
            const totalCuts = cutsH * cutsV;
            const usedArea = totalCuts * itemW * itemH;
            const totalArea = paperWidth * paperHeight;
            const efficiency = (usedArea / totalArea) * 100;
            
            this.calculation = {
                best: {
                    cuts_h: cutsH,
                    cuts_v: cutsV,
                    total_cuts: totalCuts,
                    efficiency: efficiency,
                    orientation_label: 'Horizontal'
                },
                paper_size: { width: paperWidth, height: paperHeight },
                item_size: { width: itemW, height: itemH }
            };
            
            this.showResults = true;
            this.drawCuttingPreview();
        },
        
        drawCuttingPreview() {
            const canvas = document.getElementById('cuttingCanvas');
            if (!canvas || !this.calculation) return;
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const paper = this.calculation.paper_size;
            const item = this.calculation.item_size;
            const best = this.calculation.best;
            
            // Scale to fit canvas
            const scale = Math.min(250 / paper.width, 140 / paper.height);
            const paperW = paper.width * scale;
            const paperH = paper.height * scale;
            const startX = (canvas.width - paperW) / 2;
            const startY = (canvas.height - paperH) / 2;
            
            // Draw paper background
            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(startX, startY, paperW, paperH);
            ctx.strokeStyle = '#6b7280';
            ctx.lineWidth = 2;
            ctx.strokeRect(startX, startY, paperW, paperH);
            
            // Draw cutting grid
            const itemW = item.width * scale;
            const itemH = item.height * scale;
            
            ctx.fillStyle = 'rgba(59, 130, 246, 0.2)';
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 1;
            
            for (let i = 0; i < best.cuts_h; i++) {
                for (let j = 0; j < best.cuts_v; j++) {
                    const x = startX + (i * itemW);
                    const y = startY + (j * itemH);
                    
                    ctx.fillRect(x, y, itemW, itemH);
                    ctx.strokeRect(x, y, itemW, itemH);
                }
            }
            
            // Add labels
            ctx.fillStyle = '#374151';
            ctx.font = '10px system-ui';
            ctx.textAlign = 'center';
            ctx.fillText(`${paper.width}cm`, startX + paperW/2, startY + paperH + 15);
            
            ctx.save();
            ctx.translate(startX - 15, startY + paperH/2);
            ctx.rotate(-Math.PI/2);
            ctx.fillText(`${paper.height}cm`, 0, 0);
            ctx.restore();
        },
        
        reset() {
            this.itemWidth = '';
            this.itemHeight = '';
            this.customPaperWidth = '';
            this.customPaperHeight = '';
            this.showResults = false;
            this.calculation = null;
            this.paperSize = 'carta';
        },
        
        selectPaper(id, name, width, height) {
            this.paperSize = 'custom';
            this.customPaperWidth = width;
            this.customPaperHeight = height;
        },
        
        initCanvas() {
            // Initialize canvas with example
            setTimeout(() => {
                const canvas = document.getElementById('cuttingCanvas');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#f9fafb';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    ctx.fillStyle = '#6b7280';
                    ctx.font = '12px system-ui';
                    ctx.textAlign = 'center';
                    ctx.fillText('Ingresa las medidas y presiona Calcular', canvas.width/2, canvas.height/2);
                }
            }, 100);
        }
    }
}
</script>

<!-- Enhanced CSS Animations for Livewire 3 + Alpine.js -->
<style>
/* Pulse animation for dots */
.pulse-dot {
    animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.7; }
}

/* Count up animation */
.animate-count-up {
    animation: count-up 0.8s ease-out forwards;
}

@keyframes count-up {
    from { 
        transform: translateY(10px) scale(0.8); 
        opacity: 0; 
    }
    to { 
        transform: translateY(0) scale(1); 
        opacity: 1; 
    }
}

/* Success flash animation */
.success-flash {
    animation: success-flash 0.6s ease-out;
}

@keyframes success-flash {
    0% { background-color: transparent; }
    50% { background-color: rgba(34, 197, 94, 0.1); }
    100% { background-color: transparent; }
}

/* Preview bounce animation */
.preview-bounce {
    animation: preview-bounce 0.5s ease-out;
}

@keyframes preview-bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Reset pulse animation */
.reset-pulse {
    animation: reset-pulse 0.4s ease-out;
}

@keyframes reset-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(0.98); opacity: 0.8; }
}

/* Loading shimmer effect for inputs */
.loading-shimmer {
    background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Enhanced hover effects */
.paper-calculator-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.paper-calculator-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Button press animation */
button:active {
    transform: scale(0.98);
    transition: transform 0.1s ease-in-out;
}

/* Canvas fade-in */
#cuttingCanvas {
    transition: opacity 0.3s ease-in-out;
}

#cuttingCanvas.loading {
    opacity: 0.5;
}

/* Progress bar for loading states */
.progress-bar {
    width: 100%;
    height: 2px;
    background-color: #e5e7eb;
    overflow: hidden;
    border-radius: 1px;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8, #3b82f6);
    background-size: 200% 100%;
    animation: progress-flow 1.5s ease-in-out infinite;
}

@keyframes progress-flow {
    0% { background-position: -200% 0; transform: translateX(-100%); }
    100% { background-position: 200% 0; transform: translateX(100%); }
}

/* Staggered animation delays for grid items */
.grid > div:nth-child(1) { animation-delay: 0ms; }
.grid > div:nth-child(2) { animation-delay: 50ms; }
.grid > div:nth-child(3) { animation-delay: 100ms; }
.grid > div:nth-child(4) { animation-delay: 150ms; }

/* Responsive animation adjustments */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
</div>