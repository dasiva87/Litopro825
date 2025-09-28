<?php

namespace App\Filament\Resources\Documents\Forms;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ProductDocumentForm
{
    public static function schema($context = null): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\Product'),

            Section::make('Seleccionar Producto del Inventario')
                ->description('Elige un producto existente y especifica la cantidad')
                ->schema([
                    Select::make('itemable_id')
                        ->label('Producto')
                        ->options(function () {
                            return static::getProductOptions();
                        })
                        ->searchable(['name', 'code', 'description'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) use ($context) {
                            if ($state && $context) {
                                $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($state);
                                if ($product) {
                                    $set('unit_price', $product->sale_price);
                                    $context->calculateProductTotal($get, $set);
                                }
                            }
                        })
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad Requerida')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) use ($context) {
                                    if ($context) {
                                        $context->calculateProductTotal($get, $set);
                                    }
                                }),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unitario')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('profit_margin')
                                ->label('Margen de Ganancia')
                                ->numeric()
                                ->suffix('%')
                                ->default(25)
                                ->minValue(0)
                                ->maxValue(500)
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) use ($context) {
                                    if ($context) {
                                        $context->calculateProductTotal($get, $set);
                                    }
                                }),

                            Forms\Components\TextInput::make('total_price')
                                ->label('Precio Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated()
                                ->extraAttributes(['class' => 'font-bold']),
                        ]),

                    Forms\Components\Placeholder::make('stock_warning')
                        ->content(function ($get) {
                            return static::getStockWarning($get);
                        })
                        ->html()
                        ->columnSpanFull()
                        ->visible(fn ($get) => filled($get('itemable_id'))),
                ]),
        ];
    }

    private static function getProductOptions(): array
    {
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

            return \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where(function ($query) use ($currentCompanyId, $supplierCompanyIds) {
                    $query->where('company_id', $currentCompanyId)
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
                });
        } else {
            // Para papelerías: solo productos propios
            return \App\Models\Product::where('company_id', $currentCompanyId)
                ->where('active', true)
                ->get()
                ->mapWithKeys(function ($product) {
                    $stockStatus = $product->stock == 0 ? ' (SIN STOCK)' :
                                  ($product->isLowStock() ? ' (STOCK BAJO)' : '');

                    return [$product->id => $product->name.' - $'.number_format($product->sale_price, 2).
                           ' (Stock: '.$product->stock.')'.$stockStatus];
                });
        }
    }

    private static function getStockWarning($get): string
    {
        $productId = $get('itemable_id');
        $quantity = $get('quantity') ?? 0;

        if (! $productId || ! $quantity) {
            return '';
        }

        $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($productId);
        if (! $product) {
            return '';
        }

        if ($product->stock == 0) {
            return '🔴 <strong>Producto sin stock</strong> - No hay unidades disponibles';
        } elseif ($quantity > $product->stock) {
            return '⚠️ <strong>Stock insuficiente</strong> - Solo hay '.$product->stock.' unidades disponibles';
        } elseif ($product->stock - $quantity <= $product->min_stock) {
            return '🟡 <strong>Advertencia:</strong> Después de esta venta quedarán '.($product->stock - $quantity).' unidades (por debajo del mínimo)';
        }

        return '✅ Stock suficiente ('.$product->stock.' disponibles)';
    }
}