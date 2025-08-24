<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Formulario -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4">
                {{ $this->form }}
                
                <div class="flex gap-3 mt-6">
                    @foreach ($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Resultados -->
        @if($this->results)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Resultados Numéricos -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Resultados del Cálculo
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Cortes por Pliego:</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ $this->results['cutsPerSheet'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Cortes Utilizables:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $this->results['usableCuts'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Pliegos Necesarios:</span>
                                <span class="font-bold text-orange-600 dark:text-orange-400">{{ $this->results['sheetsNeeded'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Total Cortes Producidos:</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">{{ $this->results['totalCutsProduced'] }}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Cortes Verticales:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $this->results['verticalCuts'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Cortes Horizontales:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $this->results['horizontalCuts'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Área Utilizada:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $this->results['usedAreaPercentage'] }}%</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Área Desperdiciada:</span>
                                <span class="font-bold text-red-600 dark:text-red-400">{{ $this->results['wastedAreaPercentage'] }}%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso del aprovechamiento -->
                    <div class="mt-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Aprovechamiento del Papel</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $this->results['usedAreaPercentage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ $this->results['usedAreaPercentage'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Información Detallada
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="space-y-3 text-sm">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Dimensiones</h4>
                            <div class="space-y-1 text-blue-700 dark:text-blue-300">
                                <div>Papel: {{ $this->data['paper_width'] ?? 0 }} × {{ $this->data['paper_height'] ?? 0 }} cm</div>
                                <div>Corte: {{ $this->data['cut_width'] ?? 0 }} × {{ $this->data['cut_height'] ?? 0 }} cm</div>
                                <div>Orientación: {{ ucfirst($this->data['orientation'] ?? 'horizontal') }}</div>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h4 class="font-semibold text-green-800 dark:text-green-200 mb-2">Áreas</h4>
                            <div class="space-y-1 text-green-700 dark:text-green-300">
                                <div>Área del Papel: {{ number_format($this->results['paperArea'], 2) }} cm²</div>
                                <div>Área por Corte: {{ number_format($this->results['cutArea'], 2) }} cm²</div>
                                <div>Área Total Utilizada: {{ number_format($this->results['totalCutArea'], 2) }} cm²</div>
                            </div>
                        </div>
                        
                        @if($this->results['orientation'] === 'M')
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <h4 class="font-semibold text-purple-800 dark:text-purple-200 mb-2">Máximo Aprovechamiento</h4>
                            <div class="text-purple-700 dark:text-purple-300">
                                <div>Se calculó la mejor distribución posible para maximizar el aprovechamiento del papel.</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Visualización del Corte con Canvas -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Visualización del Corte
                </h3>
            </div>
            <div class="px-6 py-4">
                <div class="flex justify-center">
                    <div class="relative">
                        <canvas id="cuttingCanvas" 
                                width="400" 
                                height="300" 
                                class="border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 rounded max-w-full"
                                style="background-color: #ddd;">
                        </canvas>
                        
                        <!-- Leyenda -->
                        <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center justify-center gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-blue-600 border border-blue-700"></div>
                                    <span>Cortes Principales</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-red-500 border border-red-600"></div>
                                    <span>Cortes Auxiliares</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 border border-gray-400"></div>
                                    <span>Área Desperdiciada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script para dibujar en el canvas -->
        <script>
            function initCanvas() {
                const canvas = document.getElementById('cuttingCanvas');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                
                // Datos del cálculo actual
                const results = @json($this->results ?? []);
                const data = @json($this->data ?? []);
                
                console.log('Results:', results);
                console.log('Data:', data);
                
                if (!results || !data || Object.keys(results).length === 0 || Object.keys(data).length === 0) {
                    console.log('No hay datos para dibujar');
                    // Limpiar canvas si no hay datos
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = '#f3f4f6';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.fillStyle = '#6b7280';
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Realiza un cálculo para ver la visualización', canvas.width/2, canvas.height/2);
                    return;
                }
                
                function drawCuttingVisualization() {
                    // Limpiar canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    
                    const paperWidth = parseFloat(data.paper_width) || 1;
                    const paperHeight = parseFloat(data.paper_height) || 1;
                    const cutWidth = parseFloat(data.cut_width) || 1;
                    const cutHeight = parseFloat(data.cut_height) || 1;
                    
                    let ladoMayorPliego = Math.max(paperWidth, paperHeight);
                    let ladoMenorPliego = Math.min(paperWidth, paperHeight);
                    
                    // Calcular escala basada en el lado mayor del pliego (como en calculadora.js)
                    const scale = 250 / ladoMayorPliego;
                    
                    let canvasWidth, canvasHeight;
                    
                    // Determinar orientación y dimensiones del canvas (replicando la lógica original)
                    if (data.orientation === 'vertical') {
                        // Orientación vertical: papel más alto que ancho
                        canvasWidth = ladoMenorPliego * scale;
                        canvasHeight = ladoMayorPliego * scale;
                        canvas.width = canvasWidth;
                        canvas.height = canvasHeight;
                        canvas.style.backgroundColor = '#ddd';
                        
                        // Dibujar cortes principales
                        dibujaCuadricula(results.verticalCuts, results.horizontalCuts, cutWidth, cutHeight, 0, 0, scale, '#286090');
                        
                        // Dibujar cortes auxiliares si existen
                        if (results.auxiliarResult && results.auxiliarResult.totalCuts > 0) {
                            // Determinar posición de cortes auxiliares
                            let auxOffsetX = results.verticalCuts * cutWidth * scale;
                            let auxOffsetY = 0;
                            
                            // Si no cabe a la derecha, ponerlos abajo
                            if (auxOffsetX + (cutHeight * scale) > canvasWidth) {
                                auxOffsetX = 0;
                                auxOffsetY = results.horizontalCuts * cutHeight * scale;
                            }
                            
                            dibujaCuadricula(results.auxiliarResult.verticalCuts, results.auxiliarResult.horizontalCuts, 
                                           cutHeight, cutWidth, auxOffsetX, auxOffsetY, scale, '#d9534f');
                        }
                        
                    } else if (data.orientation === 'horizontal') {
                        // Orientación horizontal: papel más ancho que alto
                        canvasWidth = ladoMayorPliego * scale;
                        canvasHeight = ladoMenorPliego * scale;
                        canvas.width = canvasWidth;
                        canvas.height = canvasHeight;
                        canvas.style.backgroundColor = '#ddd';
                        
                        // Dibujar cortes principales
                        dibujaCuadricula(results.verticalCuts, results.horizontalCuts, cutWidth, cutHeight, 0, 0, scale, '#286090');
                        
                        // Dibujar cortes auxiliares si existen
                        if (results.auxiliarResult && results.auxiliarResult.totalCuts > 0) {
                            let auxOffsetX = results.verticalCuts * cutWidth * scale;
                            let auxOffsetY = 0;
                            
                            // Si no cabe a la derecha, ponerlos abajo
                            if (auxOffsetX + (cutHeight * scale) > canvasWidth) {
                                auxOffsetX = 0;
                                auxOffsetY = results.horizontalCuts * cutHeight * scale;
                            }
                            
                            dibujaCuadricula(results.auxiliarResult.verticalCuts, results.auxiliarResult.horizontalCuts, 
                                           cutHeight, cutWidth, auxOffsetX, auxOffsetY, scale, '#d9534f');
                        }
                        
                    } else if (data.orientation === 'maximum') {
                        // Orientación máxima: usar la mejor distribución
                        canvasWidth = ladoMayorPliego * scale;
                        canvasHeight = ladoMenorPliego * scale;
                        canvas.width = canvasWidth;
                        canvas.height = canvasHeight;
                        canvas.style.backgroundColor = '#ddd';
                        
                        // Para orientación máxima, usar los datos del mejor arreglo
                        if (results.arrangeResult) {
                            const arrange = results.arrangeResult;
                            
                            // Si es el formato del máximo aprovechamiento (con cortesB1, cortesH1, etc.)
                            if (arrange.cortesB1 !== undefined) {
                                // Dibujar primera área
                                dibujaCuadricula(arrange.cortesB1, arrange.cortesH1, 
                                               Math.max(cutWidth, cutHeight), Math.min(cutWidth, cutHeight), 
                                               0, 0, scale, '#286090');
                                
                                // Dibujar segunda área si existe
                                if (arrange.cortesB2 > 0 && arrange.cortesH2 > 0) {
                                    let offsetX = 0;
                                    let offsetY = arrange.cortesH1 * Math.min(cutWidth, cutHeight) * scale;
                                    
                                    dibujaCuadricula(arrange.cortesB2, arrange.cortesH2, 
                                                   Math.min(cutWidth, cutHeight), Math.max(cutWidth, cutHeight),
                                                   offsetX, offsetY, scale, '#d9534f');
                                }
                            } else {
                                // Formato estándar
                                dibujaCuadricula(arrange.verticalCuts, arrange.horizontalCuts, cutWidth, cutHeight, 0, 0, scale, '#286090');
                            }
                        }
                    }
                    
                    // Agregar información de dimensiones
                    drawDimensionLabels(paperWidth, paperHeight, cutWidth, cutHeight);
                }
                
                // Función dibujaCuadricula replicada del JS original
                function dibujaCuadricula(be, bf, bh, bg, ba, bc, i, Z) {
                    if (Z === undefined) {
                        Z = "#286090";
                    }
                    
                    var bd = bc;
                    var bb = ba;
                    bh = i * bh;  // Escalar ancho del corte
                    bg = i * bg;  // Escalar alto del corte
                    
                    for (let x = 1; x <= be; x++) {
                        bc = bd;
                        for (let y = 1; y <= bf; y++) {
                            ctx.beginPath();
                            ctx.fillStyle = Z;
                            ctx.rect(ba, bc, bh, bg);
                            ctx.fill();
                            ctx.lineWidth = 0.5;
                            ctx.strokeStyle = "white";
                            ctx.stroke();
                            bc = (bg * y) + bd;
                        }
                        ba = (bh * x) + bb;
                    }
                }
                
                function drawDimensionLabels(paperWidth, paperHeight, cutWidth, cutHeight) {
                    ctx.fillStyle = '#333';
                    ctx.font = '10px Arial';
                    ctx.textAlign = 'left';
                    
                    // Información en la esquina superior
                    ctx.fillText(`Papel: ${paperWidth} × ${paperHeight} cm`, 5, 15);
                    ctx.fillText(`Corte: ${cutWidth} × ${cutHeight} cm`, 5, 30);
                    ctx.fillText(`Cortes: ${results.cutsPerSheet} por pliego`, 5, 45);
                    
                    if (results.usedAreaPercentage) {
                        ctx.fillText(`Aprovechamiento: ${results.usedAreaPercentage}%`, 5, 60);
                    }
                }
                
                // Dibujar la visualización
                drawCuttingVisualization();
            }

            // Inicializar canvas al cargar la página (solo para la primera vez)
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded - inicializando canvas inicial');
                // Solo si hay datos iniciales
                setTimeout(function() {
                    try {
                        initCanvas();
                        console.log('Canvas inicial cargado');
                    } catch (error) {
                        console.log('Sin datos iniciales para mostrar en canvas');
                    }
                }, 300);
            });
            
            // Backup: Actualizar cuando Livewire termine de procesar (por si el JS inline falla)
            document.addEventListener('livewire:updated', function() {
                console.log('Livewire updated - backup update');
                // Menor prioridad ya que el JS inline debería manejar la actualización
            });
        </script>
        @endif
    </div>
</x-filament-panels::page>
