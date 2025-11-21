<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesProducts;
use App\Models\Document;
use App\Models\Product;
use App\Models\SupplierRelationship;
use Filament\Forms\Components;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class ProductQuickHandler implements QuickActionHandlerInterface
{
    use CalculatesProducts;

    private $calculationContext;

    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Agregar Producto del Inventario')
                ->description('Selecciona un producto existente y especifica la cantidad')
                ->schema([
                    Select::make('product_id')
                        ->label('Producto')
                        ->options(function () {
                            return $this->getProductOptions();
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state) {
                                $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->with('finishings')->find($state);
                                if ($product) {
                                    $set('unit_price', $product->sale_price);
                                    $set('product_name', $product->name);
                                    $set('available_stock', $product->stock);

                                    // Cargar acabados del producto si existen
                                    if ($product->finishings->isNotEmpty()) {
                                        $finishingsData = $product->finishings->map(function ($finishing) use ($get) {
                                            $item = [
                                                'finishing_id' => (int) $finishing->id,
                                            ];

                                            // Para acabados por cantidad, usar la cantidad del producto
                                            if (in_array($finishing->measurement_unit->value, ['millar', 'rango', 'unidad'])) {
                                                if ($finishing->pivot->quantity) {
                                                    $item['quantity'] = (float) $finishing->pivot->quantity;
                                                }
                                            }

                                            // Para acabados por tamaño
                                            if ($finishing->pivot->width) {
                                                $item['width'] = (float) $finishing->pivot->width;
                                            }

                                            if ($finishing->pivot->height) {
                                                $item['height'] = (float) $finishing->pivot->height;
                                            }

                                            return $item;
                                        })->toArray();

                                        $set('finishings_data', $finishingsData);
                                    } else {
                                        $set('finishings_data', []);
                                    }

                                    if ($this->calculationContext) {
                                        $this->calculationContext->calculateProductTotalWithFinishings($get, $set);
                                    }
                                }
                            }
                        }),

                    Grid::make(2)
                        ->schema([
                            Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    if ($this->calculationContext) {
                                        $this->calculationContext->calculateProductTotalWithFinishings($get, $set);
                                    }
                                }),

                            Components\TextInput::make('unit_price')
                                ->label('Precio Unitario')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Components\TextInput::make('profit_margin')
                                ->label('Margen de Ganancia')
                                ->numeric()
                                ->suffix('%')
                                ->default(25)
                                ->minValue(0)
                                ->maxValue(500)
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    if ($this->calculationContext) {
                                        $this->calculationContext->calculateProductTotalWithFinishings($get, $set);
                                    }
                                }),

                            Components\TextInput::make('total_price')
                                ->label('Precio Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'font-bold']),
                        ]),

                    Components\Placeholder::make('stock_info')
                        ->content(function ($get) {
                            return $this->getStockInfo($get);
                        })
                        ->html()
                        ->columnSpanFull(),

                    Components\Placeholder::make('calculation_debug')
                        ->label('Debug de Cálculo')
                        ->content(function ($get) {
                            $finishingsData = $get('finishings_data') ?? [];
                            $quantity = $get('quantity') ?? 0;
                            $productId = $get('product_id');

                            if (!$productId) {
                                return '<span class="text-gray-400">Selecciona un producto</span>';
                            }

                            $product = \App\Models\Product::with('finishings')->find($productId);
                            if (!$product) {
                                return '<span class="text-red-500">Producto no encontrado</span>';
                            }

                            $info = '<div class="text-sm space-y-1">';
                            $info .= '<div><strong>Producto:</strong> ' . $product->name . '</div>';
                            $info .= '<div><strong>Acabados del producto:</strong> ' . $product->finishings->count() . '</div>';
                            $info .= '<div><strong>Acabados en formulario:</strong> ' . count($finishingsData) . '</div>';
                            $info .= '<div><strong>Cantidad:</strong> ' . $quantity . '</div>';

                            if ($product->finishings->isNotEmpty()) {
                                $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);
                                $total = 0;
                                foreach ($product->finishings as $finishing) {
                                    $params = ['quantity' => $quantity];
                                    $cost = $finishingCalculator->calculateCost($finishing, $params);
                                    $total += $cost;
                                    $info .= '<div class="text-blue-600">- ' . $finishing->name . ': $' . number_format($cost, 2) . '</div>';
                                }
                                $info .= '<div class="text-green-600 font-bold">Total acabados: $' . number_format($total, 2) . '</div>';
                            }

                            $info .= '</div>';
                            return $info;
                        })
                        ->html()
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('product_id') !== null),

                    Components\Hidden::make('product_name'),
                    Components\Hidden::make('available_stock'),
                ]),

            \Filament\Schemas\Components\Section::make('🎨 Acabados (Opcional)')
                ->description('Acabados aplicados a este producto')
                ->schema([
                    Repeater::make('finishings_data')
                        ->label('Acabados')
                        ->defaultItems(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) {
                            if ($this->calculationContext) {
                                $this->calculationContext->calculateProductTotalWithFinishings($get, $set);
                            }
                        })
                        ->reorderable(false)
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Select::make('finishing_id')
                                        ->label('Acabado')
                                        ->options(function () {
                                            $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;

                                            return \App\Models\Finishing::where('company_id', $currentCompanyId)
                                                ->where('active', true)
                                                ->get()
                                                ->mapWithKeys(function ($finishing) {
                                                    return [$finishing->id => $finishing->name . ' - ' . $finishing->measurement_unit->label()];
                                                })
                                                ->toArray();
                                        })
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->columnSpan(3),

                                    // Campos de cantidad (para MILLAR, RANGO, UNIDAD)
                                    TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->visible(function ($get) {
                                            $finishingId = $get('finishing_id');
                                            if (!$finishingId) return false;

                                            $finishing = \App\Models\Finishing::find($finishingId);
                                            if (!$finishing) return false;

                                            return in_array($finishing->measurement_unit->value, ['millar', 'rango', 'unidad']);
                                        })
                                        ->columnSpan(1),

                                    // Campos de tamaño (para TAMAÑO)
                                    TextInput::make('width')
                                        ->label('Ancho (cm)')
                                        ->numeric()
                                        ->step(0.1)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->visible(function ($get) {
                                            $finishingId = $get('finishing_id');
                                            if (!$finishingId) return false;

                                            $finishing = \App\Models\Finishing::find($finishingId);
                                            if (!$finishing) return false;

                                            return $finishing->measurement_unit->value === 'tamaño';
                                        })
                                        ->columnSpan(1),

                                    TextInput::make('height')
                                        ->label('Alto (cm)')
                                        ->numeric()
                                        ->step(0.1)
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->visible(function ($get) {
                                            $finishingId = $get('finishing_id');
                                            if (!$finishingId) return false;

                                            $finishing = \App\Models\Finishing::find($finishingId);
                                            if (!$finishing) return false;

                                            return $finishing->measurement_unit->value === 'tamaño';
                                        })
                                        ->columnSpan(1),

                                    // Placeholder para mostrar el costo calculado
                                    Placeholder::make('cost_preview')
                                        ->label('Costo Estimado')
                                        ->content(function ($get) {
                                            $finishingId = $get('finishing_id');
                                            $quantity = $get('quantity') ?? 0;
                                            $width = $get('width') ?? 0;
                                            $height = $get('height') ?? 0;

                                            if (!$finishingId) {
                                                return '<span class="text-gray-400">Seleccione un acabado</span>';
                                            }

                                            try {
                                                $finishing = \App\Models\Finishing::find($finishingId);
                                                if (!$finishing) {
                                                    return '<span class="text-red-500">Acabado no encontrado</span>';
                                                }

                                                $calculator = app(\App\Services\FinishingCalculatorService::class);

                                                $params = [];
                                                switch ($finishing->measurement_unit->value) {
                                                    case 'millar':
                                                    case 'rango':
                                                    case 'unidad':
                                                        $params = ['quantity' => (int) $quantity];
                                                        break;
                                                    case 'tamaño':
                                                        $params = [
                                                            'width' => (float) $width,
                                                            'height' => (float) $height
                                                        ];
                                                        break;
                                                }

                                                $cost = $calculator->calculateCost($finishing, $params);

                                                return '<span class="text-lg font-bold text-green-600">$' . number_format($cost, 2) . '</span>';

                                            } catch (\Exception $e) {
                                                return '<span class="text-red-500">Error: ' . $e->getMessage() . '</span>';
                                            }
                                        })
                                        ->html()
                                        ->columnSpan(3),
                                ]),
                        ])
                        ->columnSpanFull()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            isset($state['finishing_id'])
                                ? (\App\Models\Finishing::find($state['finishing_id'])?->name ?? 'Acabado')
                                : 'Acabado'
                        ),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($data['product_id']);

        if (!$product) {
            throw new \Exception('Producto no encontrado');
        }

        $quantity = $data['quantity'];

        // Verificar stock disponible y mostrar advertencia si es insuficiente
        if (!$product->hasStock($quantity)) {
            Notification::make()
                ->warning()
                ->title('⚠️ Stock Insuficiente')
                ->body("
                    **Producto:** {$product->name}

                    **Stock Disponible:** {$product->stock} unidades

                    **Cantidad Solicitada:** {$quantity} unidades

                    Por favor, reduce la cantidad solicitada o solicita reabastecimiento del producto.
                ")
                ->persistent()
                ->send();

            throw new Halt(); // Detener la ejecución sin cerrar el modal
        }

        // Cargar acabados del producto para calcular el precio total
        $product->load('finishings');

        // Calcular costo de acabados personalizados o usar los del producto
        $finishingsData = $data['finishings_data'] ?? [];
        $finishingsCostTotal = 0;

        if (!empty($finishingsData)) {
            // Usar acabados personalizados del formulario
            $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);

            foreach ($finishingsData as $finishingData) {
                if (empty($finishingData['finishing_id'])) {
                    continue;
                }

                $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
                if (!$finishing) {
                    continue;
                }

                // Preparar parámetros según el tipo de medida
                $params = [];
                switch ($finishing->measurement_unit->value) {
                    case 'millar':
                    case 'rango':
                    case 'unidad':
                        $params = ['quantity' => $finishingData['quantity'] ?? $quantity];
                        break;
                    case 'tamaño':
                        $params = [
                            'width' => $finishingData['width'] ?? 0,
                            'height' => $finishingData['height'] ?? 0,
                        ];
                        break;
                }

                $cost = $finishingCalculator->calculateCost($finishing, $params);
                $finishingsCostTotal += $cost;
            }
        } elseif ($product->finishings->isNotEmpty()) {
            // Usar acabados del producto y recalcular con la cantidad solicitada
            $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);

            foreach ($product->finishings as $finishing) {
                $params = [];

                switch ($finishing->measurement_unit->value) {
                    case 'millar':
                    case 'rango':
                    case 'unidad':
                        $params = ['quantity' => $quantity];
                        break;
                    case 'tamaño':
                        $params = [
                            'width' => $finishing->pivot->width ?? 0,
                            'height' => $finishing->pivot->height ?? 0,
                        ];
                        break;
                }

                $cost = $finishingCalculator->calculateCost($finishing, $params);
                $finishingsCostTotal += $cost;
            }
        }

        // Calcular precio total con acabados
        $profitMargin = $data['profit_margin'] ?? 0;
        $baseTotal = ($product->sale_price * $quantity) + $finishingsCostTotal;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        // Crear el DocumentItem asociado
        $documentItem = $document->items()->create([
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => 'Producto: '.$product->name,
            'quantity' => $quantity,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
            'profit_margin' => $profitMargin,
        ]);

        // Guardar acabados personalizados en item_config para referencia futura
        if (!empty($finishingsData)) {
            $documentItem->update([
                'item_config' => [
                    'finishings' => $finishingsData,
                    'finishings_cost' => $finishingsCostTotal,
                ],
            ]);
        }

        // Recalcular totales del documento
        $document->recalculateTotals();
    }

    public function getLabel(): string
    {
        return 'Producto';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public function getColor(): string
    {
        return 'primary';
    }

    public function getModalWidth(): string
    {
        return '5xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Producto agregado correctamente';
    }

    public function isVisible(): bool
    {
        return true; // Los productos están disponibles para todos los tipos de empresa
    }

    public function setCalculationContext($context): void
    {
        $this->calculationContext = $context;
    }

    private function getProductOptions(): array
    {
        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

        if (!$company) {
            return [];
        }

        if ($company->isLitografia()) {
            // Para litografías: productos propios + de proveedores aprobados
            $supplierCompanyIds = SupplierRelationship::where('client_company_id', $currentCompanyId)
                ->where('is_active', true)
                ->whereNotNull('approved_at')
                ->pluck('supplier_company_id')
                ->toArray();

            return Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                    $query->forTenant($currentCompanyId)
                          ->orWhereIn('company_id', $supplierCompanyIds);
                })
                ->where('active', true)
                ->with('company')
                ->get()
                ->mapWithKeys(function ($product) use ($currentCompanyId) {
                    $origin = $product->company_id === $currentCompanyId ? 'Propio' : $product->company->name;
                    $stockStatus = $product->stock == 0 ? ' (SIN STOCK)' :
                                  ($product->isLowStock() ? ' (STOCK BAJO)' : '');

                    return [$product->id => $product->name.' - $'.number_format($product->sale_price, 2).
                                   ' (Stock: '.$product->stock.')'.$stockStatus.' - '.$origin];
                })
                ->toArray();
        } else {
            // Para papelerías: solo productos propios
            return Product::where('active', true)
                ->forTenant($currentCompanyId)
                ->get()
                ->mapWithKeys(function ($product) {
                    $stockStatus = $product->stock == 0 ? ' (SIN STOCK)' :
                                  ($product->isLowStock() ? ' (STOCK BAJO)' : '');

                    return [
                        $product->id => $product->name.' - $'.number_format($product->sale_price, 2).
                                       ' (Stock: '.$product->stock.')'.$stockStatus,
                    ];
                })
                ->toArray();
        }
    }

    private function getStockInfo($get): string
    {
        $productId = $get('product_id');
        $quantity = $get('quantity') ?? 0;

        if (!$productId) {
            return '📦 Selecciona un producto para ver la información de stock';
        }

        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($productId);
        if (!$product) {
            return '❌ Producto no encontrado';
        }

        $content = '<div class="space-y-2">';
        $content .= '<div><strong>📋 Producto:</strong> '.$product->name.'</div>';
        $content .= '<div><strong>💰 Precio:</strong> $'.number_format($product->sale_price, 2).'</div>';
        $content .= '<div><strong>📦 Stock disponible:</strong> '.$product->stock.' unidades</div>';

        if ($quantity > 0) {
            if ($product->stock == 0) {
                $content .= '<div class="text-red-600"><strong>🔴 Sin stock</strong> - No hay unidades disponibles</div>';
            } elseif ($quantity > $product->stock) {
                $content .= '<div class="text-red-600"><strong>⚠️ Stock insuficiente</strong> - Solo hay '.$product->stock.' unidades</div>';
            } elseif ($product->stock - $quantity <= $product->min_stock) {
                $remaining = $product->stock - $quantity;
                $content .= '<div class="text-yellow-600"><strong>🟡 Advertencia:</strong> Quedarán '.$remaining.' unidades (stock bajo)</div>';
            } else {
                $content .= '<div class="text-green-600"><strong>✅ Stock suficiente</strong></div>';
            }
        }

        $content .= '</div>';

        return $content;
    }
}