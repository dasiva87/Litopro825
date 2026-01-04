<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-calculator"
        :heading="__('Resumen Financiero')">

        <style>
            .financial-summary-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 1rem;
            }
            @media (min-width: 640px) {
                .financial-summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            @media (min-width: 1024px) {
                .financial-summary-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }
        </style>

        <div class="financial-summary-grid">
            {{-- Subtotal --}}
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-primary-500 p-3">
                                <x-filament::icon
                                    icon="heroicon-o-currency-dollar"
                                    class="h-6 w-6 text-white"
                                />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Subtotal
                                </dt>
                                <dd class="mt-1">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ Number::currency($record->subtotal ?? 0, 'COP', 'es') }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Descuento --}}
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-warning-500 p-3">
                                <x-filament::icon
                                    icon="heroicon-o-tag"
                                    class="h-6 w-6 text-white"
                                />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Descuento
                                    @if($record->discount_percentage > 0)
                                        <span class="text-xs text-warning-600 dark:text-warning-400">({{ $record->discount_percentage }}%)</span>
                                    @endif
                                </dt>
                                <dd class="mt-1">
                                    @if($record->discount_amount > 0)
                                        <div class="text-2xl font-semibold text-warning-600 dark:text-warning-400">
                                            -{{ Number::currency($record->discount_amount, 'COP', 'es') }}
                                        </div>
                                    @else
                                        <div class="text-sm italic text-gray-400 dark:text-gray-500">
                                            Sin descuento
                                        </div>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Impuesto --}}
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-info-500 p-3">
                                <x-filament::icon
                                    icon="heroicon-o-receipt-percent"
                                    class="h-6 w-6 text-white"
                                />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    IVA
                                    @if($record->tax_percentage > 0)
                                        <span class="text-xs text-info-600 dark:text-info-400">({{ $record->tax_percentage }}%)</span>
                                    @endif
                                </dt>
                                <dd class="mt-1">
                                    @if($record->tax_amount > 0)
                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {{ Number::currency($record->tax_amount, 'COP', 'es') }}
                                        </div>
                                    @else
                                        <div class="text-sm italic text-gray-400 dark:text-gray-500">
                                            Sin IVA
                                        </div>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total --}}
            <div class="overflow-hidden rounded-lg bg-gradient-to-br from-success-500 to-success-600 shadow-lg ring-2 ring-success-400 dark:ring-success-600">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-white/20 p-3 backdrop-blur-sm">
                                <x-filament::icon
                                    icon="heroicon-o-banknotes"
                                    class="h-6 w-6 text-white"
                                />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-bold uppercase tracking-wide text-success-50">
                                    Total a Pagar
                                </dt>
                                <dd class="mt-2">
                                    <div class="text-3xl font-extrabold text-white drop-shadow-sm">
                                        {{ Number::currency($record->total ?? 0, 'COP', 'es') }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
