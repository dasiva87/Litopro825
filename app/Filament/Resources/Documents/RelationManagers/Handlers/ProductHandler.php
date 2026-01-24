<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use App\Models\Product;

class ProductHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
            $this->makeSection('Editar Producto', 'Modificar cantidad y margen de ganancia')
                ->schema([
                    $this->makeGrid(2)->schema([
                        Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) use ($record) {
                                $this->calculateEditTotalPrice($get, $set, $record);
                            }),

                        Components\TextInput::make('profit_margin')
                            ->label('Margen de Ganancia')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(500)
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) use ($record) {
                                $this->calculateEditTotalPrice($get, $set, $record);
                            }),
                    ]),

                    $this->makeGrid(2)->schema([
                        Components\TextInput::make('base_unit_price')
                            ->label('Precio Base Unitario')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly()
                            ->helperText('Precio del producto sin margen'),

                        Components\TextInput::make('unit_price')
                            ->label('Precio Final Unitario')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly()
                            ->extraAttributes(['class' => 'font-bold'])
                            ->helperText('Precio con margen incluido'),
                    ]),

                    Components\TextInput::make('total_price')
                        ->label('Total Final')
                        ->numeric()
                        ->prefix('$')
                        ->readOnly()
                        ->extraAttributes(['class' => 'font-bold text-lg'])
                        ->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('🎨 Acabados (Opcional)')
                ->description('Acabados aplicados a este producto')
                ->schema([
                    Repeater::make('finishings_data')
                        ->label('Acabados')
                        ->defaultItems(0)
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

                                    Placeholder::make('cost_preview')
                                        ->label('Costo Estimado')
                                        ->content(function ($get) use ($record) {
                                            $finishingId = $get('finishing_id');
                                            $quantity = $get('quantity') ?? $record->quantity ?? 0;
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
    
    public function fillForm($record): array
    {
        // Obtener el producto para calcular el precio base
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->with('finishings')->find($record->itemable_id);
        $baseUnitPrice = $product ? $product->sale_price : 0;

        // Cargar acabados desde item_config
        $finishingsData = [];
        if ($record->item_config && isset($record->item_config['finishings'])) {
            $finishingsData = $record->item_config['finishings'];
        } elseif ($product && $product->finishings->isNotEmpty()) {
            // Fallback: Si no hay acabados en item_config, cargar desde el producto
            $finishingsData = $product->finishings->map(function ($finishing) {
                $item = [
                    'finishing_id' => (int) $finishing->id,
                ];

                if ($finishing->pivot->quantity) {
                    $item['quantity'] = (float) $finishing->pivot->quantity;
                }

                if ($finishing->pivot->width) {
                    $item['width'] = (float) $finishing->pivot->width;
                }

                if ($finishing->pivot->height) {
                    $item['height'] = (float) $finishing->pivot->height;
                }

                return $item;
            })->toArray();
        }

        return [
            'quantity' => $record->quantity,
            'profit_margin' => $record->profit_margin ?? 0,
            'base_unit_price' => $baseUnitPrice,
            'unit_price' => $record->unit_price,
            'total_price' => $record->total_price,
            'finishings_data' => $finishingsData,
        ];
    }
    
    public function handleUpdate($record, array $data): void
    {
        // Obtener el producto para calcular precios
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->with('finishings')->find($record->itemable_id);

        if (!$product) {
            throw new \Exception('Producto no encontrado');
        }

        // Obtener datos del formulario
        $quantity = $data['quantity'];
        $profitMargin = $data['profit_margin'] ?? 0;
        $finishingsData = $data['finishings_data'] ?? [];

        // Calcular costo de acabados personalizados o usar los del producto
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
            // Usar acabados del producto
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
        $baseTotal = ($product->sale_price * $quantity) + $finishingsCostTotal;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        // Actualizar el DocumentItem
        $updateData = [
            'quantity' => $quantity,
            'profit_margin' => $profitMargin,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
        ];

        // Guardar acabados personalizados en item_config
        if (!empty($finishingsData)) {
            $updateData['item_config'] = [
                'finishings' => $finishingsData,
                'finishings_cost' => $finishingsCostTotal,
            ];
        }

        $record->update($updateData);
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Producto')
            ->description('Productos del inventario')
            ->icon('heroicon-o-cube')
            ->schema([
                Components\Select::make('product_id')
                    ->label('Seleccionar Producto')
                    ->options(function () {
                        $currentCompanyId = config('app.current_tenant_id') ?? auth()->user()->company_id ?? null;
                        $company = $currentCompanyId ? \App\Models\Company::find($currentCompanyId) : null;

                        if (!$company) {
                            return [];
                        }

                        if ($company->isLitografia()) {
                            // Para litografías: productos propios + de proveedores aprobados
                            $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $currentCompanyId)
                                ->where('is_active', true)
                                ->whereNotNull('approved_at')
                                ->pluck('supplier_company_id')
                                ->toArray();

                            $products = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                                $query->forTenant($currentCompanyId) // Propios (todos)
                                      ->orWhere(function ($q) use ($supplierCompanyIds) {
                                          // De proveedores: solo los públicos
                                          $q->whereIn('company_id', $supplierCompanyIds)
                                            ->where('is_public', true);
                                      });
                            })
                            ->where('active', true)
                            ->with('company')
                            ->get()
                            ->mapWithKeys(function ($product) use ($currentCompanyId) {
                                $origin = $product->company_id === $currentCompanyId ? 'Propio' : $product->company->name;
                                $label = $product->name . ' - ' . $origin;
                                return [$product->id => $label];
                            });

                            return $products->toArray();
                        } else {
                            // Para papelerías: solo productos propios
                            return Product::where('company_id', $currentCompanyId)
                                ->where('active', true)
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if ($state) {
                            $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($state);
                            if ($product) {
                                $set('unit_price', $product->sale_price);
                                $set('description', $product->description);
                                $set('stock_available', $product->stock);
                                $this->calculateTotalPrice($get, $set);
                            }
                        }
                    }),
                    
                $this->makeGrid(3)->schema([
                    $this->makeTextInput('quantity', 'Cantidad')
                        ->default(1)
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $this->calculateTotalPrice($get, $set);
                        }),

                    Components\TextInput::make('unit_price')
                        ->label('Precio Unitario')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly(),

                    Components\Placeholder::make('stock_available')
                        ->label('Stock Disponible')
                        ->content(fn ($get) => ($get('stock_available') ?? 0) . ' unidades'),
                ]),

                $this->makeGrid(2)->schema([
                    Components\TextInput::make('profit_margin')
                        ->label('Margen de Ganancia')
                        ->numeric()
                        ->suffix('%')
                        ->default(25)
                        ->minValue(0)
                        ->maxValue(500)
                        ->live()
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $this->calculateTotalPrice($get, $set);
                        }),

                    Components\TextInput::make('total_price')
                        ->label('Precio Total')
                        ->prefix('$')
                        ->numeric()
                        ->readOnly()
                        ->extraAttributes(['class' => 'font-bold text-lg']),
                ]),
            ]);
    }

    /**
     * Calcular precio total con margen de ganancia
     */
    private function calculateTotalPrice($get, $set): void
    {
        $quantity = $get('quantity') ?? 0;
        $unitPrice = $get('unit_price') ?? 0;
        $profitMargin = $get('profit_margin') ?? 0;

        if ($quantity > 0 && $unitPrice > 0) {
            // Precio base sin margen
            $baseTotal = $quantity * $unitPrice;

            // Aplicar margen de ganancia
            $finalTotal = $baseTotal * (1 + ($profitMargin / 100));

            $set('total_price', round($finalTotal, 2));
        } else {
            $set('total_price', 0);
        }
    }

    /**
     * Calcular precio total en edición
     */
    private function calculateEditTotalPrice($get, $set, $record): void
    {
        $quantity = $get('quantity') ?? 0;
        $profitMargin = $get('profit_margin') ?? 0;

        // Obtener precio base del producto
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($record->itemable_id);

        if ($product && $quantity > 0) {
            $baseTotal = $product->sale_price * $quantity;
            $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
            $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

            $set('unit_price', round($unitPriceWithMargin, 2));
            $set('total_price', round($totalPriceWithMargin, 2));
        } else {
            $set('unit_price', 0);
            $set('total_price', 0);
        }
    }
}