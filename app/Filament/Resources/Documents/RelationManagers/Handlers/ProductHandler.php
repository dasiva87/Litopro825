<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
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
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        // Obtener el producto para calcular el precio base
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($record->itemable_id);
        $baseUnitPrice = $product ? $product->sale_price : 0;

        return [
            'quantity' => $record->quantity,
            'profit_margin' => $record->profit_margin ?? 0,
            'base_unit_price' => $baseUnitPrice,
            'unit_price' => $record->unit_price,
            'total_price' => $record->total_price,
        ];
    }
    
    public function handleUpdate($record, array $data): void
    {
        // Obtener el producto para calcular precios
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($record->itemable_id);

        if (!$product) {
            throw new \Exception('Producto no encontrado');
        }

        // Calcular precios con el nuevo margen
        $quantity = $data['quantity'];
        $profitMargin = $data['profit_margin'] ?? 0;

        $baseTotal = $product->sale_price * $quantity;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $record->update([
            'quantity' => $quantity,
            'profit_margin' => $profitMargin,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
        ]);
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
                                $query->where('company_id', $currentCompanyId)
                                      ->orWhereIn('company_id', $supplierCompanyIds);
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