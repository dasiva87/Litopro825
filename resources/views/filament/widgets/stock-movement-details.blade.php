<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">📦 Información del Item</h4>
            <dl class="space-y-1">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Nombre:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->stockable->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Tipo:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $record->stockable_type === \App\Models\Product::class ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $record->stockable_type === \App\Models\Product::class ? 'Producto' : 'Papel' }}
                        </span>
                    </dd>
                </div>
                @if($record->stockable && method_exists($record->stockable, 'current_stock'))
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Stock Actual:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ number_format($record->stockable->current_stock ?? 0) }}
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">📈 Detalles del Movimiento</h4>
            <dl class="space-y-1">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Tipo:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $record->type === 'in' ? 'bg-green-100 text-green-800' :
                               ($record->type === 'out' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ match($record->type) {
                                'in' => '📈 Entrada',
                                'out' => '📉 Salida',
                                'adjustment' => '⚖️ Ajuste',
                                default => ucfirst($record->type)
                            } }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Cantidad:</dt>
                    <dd class="text-lg font-bold
                        {{ $record->type === 'in' ? 'text-green-600' :
                           ($record->type === 'out' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ $record->type === 'in' ? '+' : ($record->type === 'out' ? '-' : '') }}{{ number_format($record->quantity) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600 dark:text-gray-400">Razón:</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ match($record->reason) {
                            'sale' => '💰 Venta',
                            'purchase' => '🛒 Compra',
                            'return' => '↩️ Devolución',
                            'damage' => '💥 Daño',
                            'adjustment' => '⚖️ Ajuste',
                            'transfer' => '🔄 Transferencia',
                            default => ucfirst($record->reason)
                        } }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">ℹ️ Información Adicional</h4>
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <dt class="text-sm text-gray-600 dark:text-gray-400">Fecha y Hora:</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $record->created_at->format('d/m/Y H:i:s') }}
                </dd>
                <dd class="text-xs text-gray-500">
                    {{ $record->created_at->diffForHumans() }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600 dark:text-gray-400">Usuario Responsable:</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $record->user->name ?? 'Sistema Automático' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-600 dark:text-gray-400">ID Movimiento:</dt>
                <dd class="text-sm font-mono text-gray-900 dark:text-gray-100">
                    #{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}
                </dd>
            </div>
        </dl>
    </div>

    @if($record->notes)
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Notas del Movimiento
        </h4>
        <p class="text-sm text-blue-800 dark:text-blue-200">
            {{ $record->notes }}
        </p>
    </div>
    @endif

    @if($record->stockable && $record->stockable_type === \App\Models\Product::class)
        @php
            $product = $record->stockable;
        @endphp
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
            <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">📋 Información del Producto</h4>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($product->sku)
                <div>
                    <dt class="text-sm text-green-700 dark:text-green-300">SKU:</dt>
                    <dd class="text-sm font-mono text-green-900 dark:text-green-100">{{ $product->sku }}</dd>
                </div>
                @endif
                @if($product->price)
                <div>
                    <dt class="text-sm text-green-700 dark:text-green-300">Precio Unitario:</dt>
                    <dd class="text-sm font-medium text-green-900 dark:text-green-100">
                        ${{ number_format($product->price, 0) }} COP
                    </dd>
                </div>
                @endif
                @if($product->category)
                <div>
                    <dt class="text-sm text-green-700 dark:text-green-300">Categoría:</dt>
                    <dd class="text-sm text-green-900 dark:text-green-100">{{ $product->category }}</dd>
                </div>
                @endif
                @if($product->min_stock)
                <div>
                    <dt class="text-sm text-green-700 dark:text-green-300">Stock Mínimo:</dt>
                    <dd class="text-sm text-green-900 dark:text-green-100">{{ number_format($product->min_stock) }}</dd>
                </div>
                @endif
            </dl>
        </div>
    @endif
</div>