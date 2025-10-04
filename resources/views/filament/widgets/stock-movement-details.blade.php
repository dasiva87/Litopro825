<div class="space-y-6">
    {{-- Header Summary Card --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                @if($record->type === 'in')
                    <x-filament::icon icon="heroicon-o-arrow-up-circle" class="w-6 h-6 text-success-500" />
                @elseif($record->type === 'out')
                    <x-filament::icon icon="heroicon-o-arrow-down-circle" class="w-6 h-6 text-danger-500" />
                @else
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-6 h-6 text-warning-500" />
                @endif
                <span>Movimiento #{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
        </x-slot>

        <x-slot name="description">
            {{ $record->created_at->format('d/m/Y H:i:s') }} • {{ $record->created_at->diffForHumans() }}
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Movimiento</span>
                <x-filament::badge
                    :color="match($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray'
                    }"
                    size="lg"
                >
                    {{ match($record->type) {
                        'in' => 'Entrada',
                        'out' => 'Salida',
                        'adjustment' => 'Ajuste',
                        default => ucfirst($record->type)
                    } }}
                </x-filament::badge>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Cantidad</span>
                <div class="text-2xl font-bold {{ $record->type === 'in' ? 'text-success-600 dark:text-success-400' : ($record->type === 'out' ? 'text-danger-600 dark:text-danger-400' : 'text-warning-600 dark:text-warning-400') }}">
                    {{ $record->type === 'in' ? '+' : ($record->type === 'out' ? '-' : '') }}{{ number_format($record->quantity) }}
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Razón</span>
                <x-filament::badge color="info" size="lg">
                    {{ match($record->reason) {
                        'sale' => 'Venta',
                        'purchase' => 'Compra',
                        'return' => 'Devolución',
                        'damage' => 'Daño',
                        'adjustment' => 'Ajuste',
                        'transfer' => 'Transferencia',
                        default => ucfirst($record->reason)
                    } }}
                </x-filament::badge>
            </div>
        </div>
    </x-filament::section>

    {{-- Item Information --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-cube" class="w-5 h-5" />
                Información del Item
            </div>
        </x-slot>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                <dd class="text-base font-semibold">{{ $record->stockable->name }}</dd>
            </div>

            <div class="flex flex-col gap-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</dt>
                <dd>
                    <x-filament::badge
                        :color="$record->stockable_type === \App\Models\Product::class ? 'success' : 'info'"
                    >
                        {{ $record->stockable_type === \App\Models\Product::class ? 'Producto' : 'Papel' }}
                    </x-filament::badge>
                </dd>
            </div>

            @if($record->stockable && method_exists($record->stockable, 'current_stock'))
            <div class="flex flex-col gap-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Actual</dt>
                <dd class="text-base font-semibold">{{ number_format($record->stockable->current_stock ?? 0) }}</dd>
            </div>
            @endif

            <div class="flex flex-col gap-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario Responsable</dt>
                <dd class="text-base font-semibold">{{ $record->user->name ?? 'Sistema Automático' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Product Details (if applicable) --}}
    @if($record->stockable && $record->stockable_type === \App\Models\Product::class)
        @php
            $product = $record->stockable;
        @endphp
        <x-filament::section icon="heroicon-o-information-circle" icon-color="success">
            <x-slot name="heading">Detalles del Producto</x-slot>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($product->sku)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                    <dd class="text-base font-mono">{{ $product->sku }}</dd>
                </div>
                @endif

                @if($product->price)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Precio Unitario</dt>
                    <dd class="text-base font-semibold">${{ number_format($product->price, 0) }} COP</dd>
                </div>
                @endif

                @if($product->category)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Categoría</dt>
                    <dd class="text-base">{{ $product->category }}</dd>
                </div>
                @endif

                @if($product->min_stock)
                <div class="flex flex-col gap-1">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Mínimo</dt>
                    <dd class="text-base">{{ number_format($product->min_stock) }}</dd>
                </div>
                @endif
            </dl>
        </x-filament::section>
    @endif

    {{-- Notes --}}
    @if($record->notes)
    <x-filament::section icon="heroicon-o-document-text" icon-color="warning">
        <x-slot name="heading">Notas del Movimiento</x-slot>

        <div class="prose dark:prose-invert max-w-none">
            <p class="text-sm">{{ $record->notes }}</p>
        </div>
    </x-filament::section>
    @endif
</div>
