<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface;
use App\Filament\Resources\Documents\RelationManagers\Traits\CalculatesProducts;
use App\Models\Document;
use App\Models\Product;
use App\Models\SupplierRelationship;
use Filament\Forms\Components;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;

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
                                $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($state);
                                if ($product) {
                                    $set('unit_price', $product->sale_price);
                                    $set('product_name', $product->name);
                                    $set('available_stock', $product->stock);
                                    if ($this->calculationContext) {
                                        $this->calculationContext->calculateProductTotal($get, $set);
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
                                        $this->calculationContext->calculateProductTotal($get, $set);
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
                                        $this->calculationContext->calculateProductTotal($get, $set);
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

                    Components\Hidden::make('product_name'),
                    Components\Hidden::make('available_stock'),
                ]),
        ];
    }

    public function handleCreate(array $data, Document $document): void
    {
        $product = Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($data['product_id']);

        if (!$product) {
            throw new \Exception('Producto no encontrado');
        }

        $quantity = $data['quantity'];

        // Verificar stock disponible
        if (!$product->hasStock($quantity)) {
            throw new \Exception('Stock insuficiente. Disponible: '.$product->stock.', Solicitado: '.$quantity);
        }

        // Calcular precio con margen de ganancia del formulario
        $profitMargin = $data['profit_margin'] ?? 0;
        $baseTotal = $product->sale_price * $quantity;
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        // Crear el DocumentItem asociado
        $document->items()->create([
            'itemable_type' => 'App\\Models\\Product',
            'itemable_id' => $product->id,
            'description' => 'Producto: '.$product->name,
            'quantity' => $quantity,
            'unit_price' => round($unitPriceWithMargin, 2),
            'total_price' => round($totalPriceWithMargin, 2),
            'profit_margin' => $profitMargin,
        ]);

        // Recalcular totales del documento
        $document->recalculateTotals();
    }

    public function getLabel(): string
    {
        return 'Producto Rápido';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public function getColor(): string
    {
        return 'purple';
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
                })
                ->toArray();
        } else {
            // Para papelerías: solo productos propios
            return Product::where('active', true)
                ->where('company_id', $currentCompanyId)
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