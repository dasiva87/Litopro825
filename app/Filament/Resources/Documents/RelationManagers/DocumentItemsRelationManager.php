<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\SimpleItem;
use App\Models\DocumentItem;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items de la Cotización';
    
    protected static ?string $inverseRelationship = 'document';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

    private function shouldShowSizeFields(?int $finishingId): bool
    {
        if (!$finishingId) {
            return false;
        }
        
        $finishing = \App\Models\Finishing::find($finishingId);
        return $finishing && $finishing->measurement_unit->value === 'tamaño';
    }

    private function calculateFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 0;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;
        
        \Log::info('calculateFinishingCost called', [
            'finishing_id' => $finishingId,
            'quantity' => $quantity,
            'width' => $width,
            'height' => $height
        ]);
        
        if ($finishingId && $quantity > 0) {
            try {
                $finishing = \App\Models\Finishing::find($finishingId);
                if ($finishing) {
                    $calculator = app(\App\Services\FinishingCalculatorService::class);
                    $cost = $calculator->calculateCost($finishing, [
                        'quantity' => $quantity,
                        'width' => $width,
                        'height' => $height,
                    ]);
                    
                    \Log::info('Calculated cost', ['cost' => $cost]);
                    $set('calculated_cost', $cost);
                    
                    // Recalcular el total del item incluyendo todos los acabados
                    $this->recalculateItemTotal($set, $get);
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating finishing cost', ['error' => $e->getMessage()]);
                $set('calculated_cost', 0);
            }
        } else {
            $set('calculated_cost', 0);
            // Recalcular el total del item incluso si se quita un acabado
            $this->recalculateItemTotal($set, $get);
        }
    }
    
    private function recalculateItemTotal($set, $get): void
    {
        try {
            // Determinar la ruta de acceso basado en el contexto (desde repeater o desde form principal)
            $isFromRepeater = str_contains(json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)), 'calculateFinishingCost');
            $pathPrefix = $isFromRepeater ? '../../' : '';
            
            // Obtener el precio base del item
            $basePrice = 0;
            $quantity = $get($pathPrefix . 'quantity') ?? 1;
            
            if ($get($pathPrefix . 'item_type') === 'digital') {
                // Para items digitales, obtener el precio base del DigitalItem
                $itemableId = $get($pathPrefix . 'itemable_id');
                
                if ($itemableId) {
                    $digitalItem = \App\Models\DigitalItem::find($itemableId);
                    if ($digitalItem) {
                        $unitValue = $digitalItem->unit_value;
                        
                        if ($digitalItem->pricing_type === 'size') {
                            $width = $get($pathPrefix . 'width') ?? 0;
                            $height = $get($pathPrefix . 'height') ?? 0;
                            $area = ($width / 100) * ($height / 100); // convertir cm a m²
                            $basePrice = $area * $unitValue * $quantity;
                        } else {
                            $basePrice = $unitValue * $quantity;
                        }
                    }
                }
            }
            
            // Sumar todos los costos de acabados
            $finishings = $get($pathPrefix . 'finishings') ?? [];
            $finishingsCost = 0;
            
            foreach ($finishings as $finishing) {
                if (isset($finishing['calculated_cost'])) {
                    $finishingsCost += (float) $finishing['calculated_cost'];
                }
            }
            
            $totalPrice = $basePrice + $finishingsCost;
            $unitPrice = $quantity > 0 ? $totalPrice / $quantity : 0;
            
            \Log::info('Recalculating item total', [
                'base_price' => $basePrice,
                'finishings_cost' => $finishingsCost,
                'total_price' => $totalPrice,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'context' => $isFromRepeater ? 'repeater' : 'main_form'
            ]);
            
            // Actualizar los precios en el formulario principal
            $set($pathPrefix . 'unit_price', round($unitPrice, 2));
            $set($pathPrefix . 'total_price', round($totalPrice, 2));
            
        } catch (\Exception $e) {
            \Log::error('Error recalculating item total', ['error' => $e->getMessage()]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Tipo de Item')
                        ->schema([
                            Select::make('item_type')
                                ->label('Tipo de Item')
                                ->options([
                                    'simple' => 'Item Sencillo (montaje, papel, máquina, tintas)',
                                    'talonario' => 'Talonario',
                                    'magazine' => 'Revista',
                                    'digital' => 'Digital',
                                    'custom' => 'Personalizado',
                                    'product' => 'Producto (desde inventario)',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    // Limpiar datos cuando se cambia el tipo
                                    $set('itemable_type', null);
                                    $set('itemable_id', null);
                                }),
                        ]),
                        
                    Wizard\Step::make('Detalles del Item')
                        ->schema(function ($get) {
                            $itemType = $get('item_type');
                            
                            if ($itemType === 'simple') {
                                return [
                                    Forms\Components\Hidden::make('itemable_type')
                                        ->default('App\\Models\\SimpleItem'),
                                        
                                    // Incluir formulario de SimpleItem inline
                                    Group::make()
                                        ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                                        ->columnSpanFull(),
                                ];
                            }
                            
                            if ($itemType === 'digital') {
                                return [
                                    Forms\Components\Hidden::make('itemable_type')
                                        ->default('App\\Models\\DigitalItem'),
                                        
                                    \Filament\Schemas\Components\Section::make('Seleccionar Item Digital')
                                        ->description('Elige un item digital existente y especifica parámetros')
                                        ->schema([
                                            Select::make('itemable_id')
                                                ->label('Item Digital')
                                                ->options(function () {
                                                    return \App\Models\DigitalItem::where('company_id', auth()->user()->company_id)
                                                        ->where('active', true)
                                                        ->get()
                                                        ->mapWithKeys(function ($item) {
                                                            return [$item->id => $item->code . ' - ' . $item->description . ' (' . $item->pricing_type_name . ')'];
                                                        });
                                                })
                                                ->searchable(['code', 'description'])
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->columnSpanFull(),
                                                
                                            \Filament\Schemas\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('quantity')
                                                        ->label('Cantidad')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1)
                                                        ->suffix('unidades')
                                                        ->live()
                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                            $this->recalculateItemTotal($set, $get);
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('width')
                                                        ->label('Ancho (cm)')
                                                        ->numeric()
                                                        ->visible(function ($get) {
                                                            $itemId = $get('itemable_id');
                                                            if ($itemId) {
                                                                $item = \App\Models\DigitalItem::find($itemId);
                                                                return $item && $item->pricing_type === 'size';
                                                            }
                                                            return false;
                                                        })
                                                        ->required(function ($get) {
                                                            $itemId = $get('itemable_id');
                                                            if ($itemId) {
                                                                $item = \App\Models\DigitalItem::find($itemId);
                                                                return $item && $item->pricing_type === 'size';
                                                            }
                                                            return false;
                                                        })
                                                        ->live()
                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                            $this->recalculateItemTotal($set, $get);
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('height')
                                                        ->label('Alto (cm)')
                                                        ->numeric()
                                                        ->visible(function ($get) {
                                                            $itemId = $get('itemable_id');
                                                            if ($itemId) {
                                                                $item = \App\Models\DigitalItem::find($itemId);
                                                                return $item && $item->pricing_type === 'size';
                                                            }
                                                            return false;
                                                        })
                                                        ->required(function ($get) {
                                                            $itemId = $get('itemable_id');
                                                            if ($itemId) {
                                                                $item = \App\Models\DigitalItem::find($itemId);
                                                                return $item && $item->pricing_type === 'size';
                                                            }
                                                            return false;
                                                        })
                                                        ->live()
                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                            $this->recalculateItemTotal($set, $get);
                                                        }),
                                                ]),
                                                
                                            // Sección de Acabados Completa
                                            \Filament\Schemas\Components\Section::make('🎨 Acabados Opcionales')
                                                ->description('Agrega acabados adicionales que se calcularán automáticamente')
                                                ->schema([
                                                    Forms\Components\Repeater::make('finishings')
                                                        ->label('Acabados')
                                                        ->schema([
                                                            Forms\Components\Select::make('finishing_id')
                                                                ->label('Acabado')
                                                                ->options(function () {
                                                                    return \App\Models\Finishing::where('active', true)
                                                                        ->where('company_id', auth()->user()->company_id)
                                                                        ->get()
                                                                        ->mapWithKeys(function ($finishing) {
                                                                            return [
                                                                                $finishing->id => $finishing->code . ' - ' . $finishing->name . ' (' . $finishing->measurement_unit->label() . ')'
                                                                            ];
                                                                        });
                                                                })
                                                                ->required()
                                                                ->live()
                                                                ->searchable()
                                                                ->afterStateUpdated(function ($set, $get, $state) {
                                                                    $this->calculateFinishingCost($set, $get);
                                                                }),
                                                                
                                                            Grid::make(3)
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('quantity')
                                                                        ->label('Cantidad')
                                                                        ->numeric()
                                                                        ->default(1)
                                                                        ->required()
                                                                        ->live()
                                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                                            $this->calculateFinishingCost($set, $get);
                                                                        }),
                                                                        
                                                                    Forms\Components\TextInput::make('width')
                                                                        ->label('Ancho (cm)')
                                                                        ->numeric()
                                                                        ->step(0.01)
                                                                        ->live()
                                                                        ->visible(fn ($get) => $this->shouldShowSizeFields($get('finishing_id')))
                                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                                            $this->calculateFinishingCost($set, $get);
                                                                        }),
                                                                        
                                                                    Forms\Components\TextInput::make('height')
                                                                        ->label('Alto (cm)')
                                                                        ->numeric()
                                                                        ->step(0.01)
                                                                        ->live()
                                                                        ->visible(fn ($get) => $this->shouldShowSizeFields($get('finishing_id')))
                                                                        ->afterStateUpdated(function ($set, $get, $state) {
                                                                            $this->calculateFinishingCost($set, $get);
                                                                        }),
                                                                ]),
                                                                
                                                            Forms\Components\Placeholder::make('calculated_cost_display')
                                                                ->label('Costo Calculado')
                                                                ->content(function ($get) {
                                                                    $finishingId = $get('finishing_id');
                                                                    $quantity = $get('quantity') ?? 0;
                                                                    $width = $get('width') ?? 0;
                                                                    $height = $get('height') ?? 0;
                                                                    
                                                                    if (!$finishingId || $quantity <= 0) {
                                                                        return '$0.00';
                                                                    }
                                                                    
                                                                    try {
                                                                        $finishing = \App\Models\Finishing::find($finishingId);
                                                                        if (!$finishing) {
                                                                            return 'Acabado no encontrado';
                                                                        }
                                                                        
                                                                        $calculator = app(\App\Services\FinishingCalculatorService::class);
                                                                        $cost = $calculator->calculateCost($finishing, [
                                                                            'quantity' => $quantity,
                                                                            'width' => $width,
                                                                            'height' => $height,
                                                                        ]);
                                                                        
                                                                        return '$' . number_format($cost, 2);
                                                                    } catch (\Exception $e) {
                                                                        return 'Error: ' . $e->getMessage();
                                                                    }
                                                                })
                                                                ->live(),
                                                                
                                                            Forms\Components\Hidden::make('calculated_cost')
                                                                ->live(),
                                                        ])
                                                        ->defaultItems(0)
                                                        ->reorderable()
                                                        ->collapsible()
                                                        ->columnSpanFull(),
                                                ])
                                                ->collapsible()
                                                ->persistCollapsed(false),

                                            Forms\Components\Placeholder::make('digital_preview')
                                                ->content(function ($get) {
                                                    $itemId = $get('itemable_id');
                                                    $quantity = $get('quantity') ?? 1;
                                                    $width = $get('width') ?? 0;
                                                    $height = $get('height') ?? 0;
                                                    
                                                    if (!$itemId) {
                                                        return '📋 Selecciona un item digital para ver el cálculo';
                                                    }
                                                    
                                                    $item = \App\Models\DigitalItem::find($itemId);
                                                    if (!$item) {
                                                        return '❌ Item digital no encontrado';
                                                    }
                                                    
                                                    $params = ['quantity' => $quantity];
                                                    
                                                    if ($item->pricing_type === 'size') {
                                                        $params['width'] = $width;
                                                        $params['height'] = $height;
                                                    }
                                                    
                                                    $errors = $item->validateParameters($params);
                                                    
                                                    $content = '<div class="space-y-2">';
                                                    $content .= '<div><strong>📋 Item:</strong> ' . $item->description . '</div>';
                                                    $content .= '<div><strong>📐 Tipo:</strong> ' . $item->pricing_type_name . '</div>';
                                                    $content .= '<div><strong>💰 Valor unitario:</strong> ' . $item->formatted_unit_value . '</div>';
                                                    
                                                    if (!empty($errors)) {
                                                        $content .= '<div class="text-red-600 mt-2">';
                                                        foreach ($errors as $error) {
                                                            $content .= '<div>❌ ' . $error . '</div>';
                                                        }
                                                        $content .= '</div>';
                                                    } else {
                                                        $baseTotalPrice = $item->calculateTotalPrice($params);
                                                        
                                                        if ($item->pricing_type === 'size' && $width > 0 && $height > 0) {
                                                            $area = ($width / 100) * ($height / 100);
                                                            $content .= '<div><strong>📏 Área:</strong> ' . number_format($area, 4) . ' m²</div>';
                                                        }
                                                        
                                                        $content .= '<div class="mt-2 p-2 bg-blue-50 rounded">';
                                                        $content .= '<div><strong>💵 TOTAL:</strong> $' . number_format($baseTotalPrice, 2) . '</div>';
                                                        $content .= '</div>';
                                                        
                                                        $content .= '<div class="text-green-600"><strong>✅ Cálculo válido</strong></div>';
                                                    }
                                                    
                                                    $content .= '</div>';
                                                    return $content;
                                                })
                                                ->html()
                                                ->columnSpanFull()
                                                ->visible(fn ($get) => filled($get('itemable_id'))),
                                        ]),
                                ];
                            }
                            
                            if ($itemType === 'custom') {
                                return [
                                    Forms\Components\Hidden::make('itemable_type')
                                        ->default('App\\Models\\CustomItem'),
                                        
                                    \Filament\Schemas\Components\Section::make('Item Personalizado')
                                        ->description('Agrega un item personalizado con precios manuales')
                                        ->schema([
                                            Forms\Components\Textarea::make('description')
                                                ->label('Descripción del Item')
                                                ->required()
                                                ->rows(3)
                                                ->placeholder('Describe el producto o servicio personalizado')
                                                ->columnSpanFull(),
                                                
                                            \Filament\Schemas\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('quantity')
                                                        ->label('Cantidad')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1)
                                                        ->suffix('unidades')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $get, $set) {
                                                            $unitPrice = $get('unit_price') ?? 0;
                                                            $total = $state * $unitPrice;
                                                            $set('total_price', $total);
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('unit_price')
                                                        ->label('Precio Unitario')
                                                        ->numeric()
                                                        ->required()
                                                        ->prefix('$')
                                                        ->step(0.01)
                                                        ->minValue(0)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $get, $set) {
                                                            $quantity = $get('quantity') ?? 1;
                                                            $total = $quantity * $state;
                                                            $set('total_price', $total);
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('total_price')
                                                        ->label('Precio Total')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->disabled()
                                                        ->dehydrated(),
                                                ]),
                                                
                                            Forms\Components\Textarea::make('notes')
                                                ->label('Notas Adicionales')
                                                ->rows(2)
                                                ->placeholder('Notas internas sobre este item (opcional)')
                                                ->columnSpanFull(),
                                                
                                            Forms\Components\Placeholder::make('custom_preview')
                                                ->content(function ($get) {
                                                    $description = $get('description');
                                                    $quantity = $get('quantity') ?? 1;
                                                    $unitPrice = $get('unit_price') ?? 0;
                                                    $totalPrice = $quantity * $unitPrice;
                                                    
                                                    if (empty($description)) {
                                                        return '📝 Agrega una descripción para ver la vista previa';
                                                    }
                                                    
                                                    $content = '<div class="p-4 bg-green-50 rounded space-y-2">';
                                                    $content .= '<h4 class="font-semibold text-green-800">Vista Previa del Item</h4>';
                                                    $content .= '<div><strong>📋 Descripción:</strong> ' . e($description) . '</div>';
                                                    $content .= '<div><strong>📊 Cantidad:</strong> ' . number_format($quantity) . ' unidades</div>';
                                                    $content .= '<div><strong>💰 Precio unitario:</strong> $' . number_format($unitPrice, 2) . '</div>';
                                                    $content .= '<div class="mt-2 p-2 bg-white rounded border border-green-200">';
                                                    $content .= '<div class="text-lg font-semibold text-green-600">💵 Total: $' . number_format($totalPrice, 2) . '</div>';
                                                    $content .= '</div>';
                                                    $content .= '<div class="text-green-600"><strong>✅ Item personalizado listo</strong></div>';
                                                    $content .= '</div>';
                                                    
                                                    return $content;
                                                })
                                                ->html()
                                                ->columnSpanFull(),
                                        ]),
                                ];
                            }
                            
                            if ($itemType === 'product') {
                                return [
                                    Forms\Components\Hidden::make('itemable_type')
                                        ->default('App\\Models\\Product'),
                                        
                                    \Filament\Schemas\Components\Section::make('Seleccionar Producto del Inventario')
                                        ->description('Elige un producto existente y especifica la cantidad')
                                        ->schema([
                                            Select::make('itemable_id')
                                                ->label('Producto')
                                                ->options(function () {
                                                    return \App\Models\Product::where('company_id', auth()->user()->company_id)
                                                        ->where('active', true)
                                                        ->get()
                                                        ->mapWithKeys(function ($product) {
                                                            $stockStatus = $product->stock == 0 ? ' (SIN STOCK)' : 
                                                                          ($product->isLowStock() ? ' (STOCK BAJO)' : '');
                                                            return [$product->id => $product->name . ' - $' . number_format($product->sale_price, 2) . 
                                                                   ' (Stock: ' . $product->stock . ')' . $stockStatus];
                                                        });
                                                })
                                                ->searchable(['name', 'code', 'description'])
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->columnSpanFull(),
                                                
                                            \Filament\Schemas\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('quantity')
                                                        ->label('Cantidad Requerida')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1)
                                                        ->suffix('unidades')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            // Calcular precio total automáticamente
                                                            if ($get('itemable_id')) {
                                                                $product = \App\Models\Product::find($get('itemable_id'));
                                                                if ($product) {
                                                                    $total = $product->sale_price * ($state ?? 0);
                                                                    $set('unit_price', $product->sale_price);
                                                                    $set('total_price', $total);
                                                                }
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('unit_price')
                                                        ->label('Precio Unitario')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->disabled()
                                                        ->dehydrated(),
                                                        
                                                    Forms\Components\TextInput::make('total_price')
                                                        ->label('Precio Total')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->disabled()
                                                        ->dehydrated(),
                                                ]),
                                                
                                            Forms\Components\Placeholder::make('stock_warning')
                                                ->content(function ($get) {
                                                    $productId = $get('itemable_id');
                                                    $quantity = $get('quantity') ?? 0;
                                                    
                                                    if (!$productId || !$quantity) {
                                                        return '';
                                                    }
                                                    
                                                    $product = \App\Models\Product::find($productId);
                                                    if (!$product) {
                                                        return '';
                                                    }
                                                    
                                                    if ($product->stock == 0) {
                                                        return '🔴 <strong>Producto sin stock</strong> - No hay unidades disponibles';
                                                    } elseif ($quantity > $product->stock) {
                                                        return '⚠️ <strong>Stock insuficiente</strong> - Solo hay ' . $product->stock . ' unidades disponibles';
                                                    } elseif ($product->stock - $quantity <= $product->min_stock) {
                                                        return '🟡 <strong>Advertencia:</strong> Después de esta venta quedarán ' . ($product->stock - $quantity) . ' unidades (por debajo del mínimo)';
                                                    }
                                                    
                                                    return '✅ Stock suficiente (' . $product->stock . ' disponibles)';
                                                })
                                                ->html()
                                                ->columnSpanFull()
                                                ->visible(fn ($get) => filled($get('itemable_id'))),
                                        ]),
                                ];
                            }
                            
                            // Para otros tipos de item, mostrar mensaje temporal
                            return [
                                Forms\Components\Placeholder::make('not_implemented')
                                    ->content('Este tipo de item aún no está implementado.')
                                    ->columnSpanFull(),
                            ];
                        })
                        ->visible(fn ($get) => filled($get('item_type'))),
                ])
                ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('itemable_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\Product' => 'Producto',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Digital',
                        'App\\Models\\CustomItem' => 'Personalizado',
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\\Models\\SimpleItem' => 'success',
                        'App\\Models\\Product' => 'purple',
                        'App\\Models\\TalonarioItem' => 'warning',
                        'App\\Models\\MagazineItem' => 'info',
                        'App\\Models\\DigitalItem' => 'primary',
                        'App\\Models\\CustomItem' => 'secondary',
                        default => 'gray'
                    }),
                    
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar quantity del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->quantity;
                        }
                        // Para SimpleItems, usar quantity del item relacionado
                        return $record->itemable ? $record->itemable->quantity : $record->quantity;
                    })
                    ->numeric()
                    ->suffix(' uds'),
                    
                TextColumn::make('description')
                    ->label('Descripción')
                    ->getStateUsing(function ($record) {
                        // Para productos, mostrar el nombre del producto
                        if ($record->itemable_type === 'App\\Models\\Product' && $record->itemable) {
                            return $record->itemable->name;
                        }
                        // Para SimpleItems, usar la descripción del item
                        if ($record->itemable && isset($record->itemable->description)) {
                            return $record->itemable->description;
                        }
                        return $record->description;
                    })
                    ->limit(50)
                    ->searchable(),
                    
                TextColumn::make('unit_price')
                    ->label('Precio Unitario')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar unit_price del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->unit_price;
                        }
                        // Para SimpleItems, calcular desde final_price
                        if ($record->itemable && isset($record->itemable->final_price) && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para TalonarioItems, calcular desde final_price
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para MagazineItems, calcular desde final_price  
                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable && $record->itemable->quantity > 0) {
                            return $record->itemable->final_price / $record->itemable->quantity;
                        }
                        // Para CustomItems, usar unit_price directo
                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            return $record->itemable->unit_price ?? 0;
                        }
                        // Para DigitalItems, usar método que incluye acabados
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            return $record->getUnitPriceWithFinishings();
                        }
                        return $record->unit_price ?? 0;
                    })
                    ->money('COP'),
                    
                TextColumn::make('total_price')
                    ->label('Precio Total')
                    ->getStateUsing(function ($record) {
                        // Para productos, usar total_price del DocumentItem
                        if ($record->itemable_type === 'App\\Models\\Product') {
                            return $record->total_price;
                        }
                        // Para CustomItems, usar total_price calculado
                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            return $record->itemable->total_price ?? 0;
                        }
                        // Para SimpleItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para TalonarioItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para MagazineItems, usar final_price del item
                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        // Para DigitalItems, usar método que incluye acabados
                        if ($record->itemable_type === 'App\\Models\\DigitalItem') {
                            return $record->getTotalPriceWithFinishings();
                        }
                        // Fallback al total_price del DocumentItem
                        return $record->total_price ?? 0;
                    })
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('finishings_info')
                    ->label('Acabados')
                    ->getStateUsing(function ($record) {
                        if ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            $finishings = $record->itemable->finishings;
                            if ($finishings->count() > 0) {
                                $names = $finishings->pluck('name')->take(2)->implode(', ');
                                $total = $finishings->count();
                                return $total > 2 ? $names . " (+" . ($total - 2) . " más)" : $names;
                            }
                        }
                        return '—';
                    })
                    ->badge()
                    ->color(fn($state) => $state !== '—' ? 'primary' : 'gray')
                    ->visible(fn() => true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('itemable_type')
                    ->label('Tipo de Item')
                    ->options([
                        'App\\Models\\SimpleItem' => 'Sencillo',
                        'App\\Models\\Product' => 'Producto',
                        'App\\Models\\TalonarioItem' => 'Talonario',
                        'App\\Models\\MagazineItem' => 'Revista',
                        'App\\Models\\DigitalItem' => 'Digital',
                        'App\\Models\\CustomItem' => 'Personalizado',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Item')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Primero crear el SimpleItem si es de tipo simple
                        if ($data['item_type'] === 'simple' && $data['itemable_type'] === 'App\\Models\\SimpleItem') {
                            // Extraer datos del SimpleItem del formulario anidado
                            $simpleItemData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id', 'quantity', 'unit_price', 'total_price']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            // Crear el SimpleItem
                            $simpleItem = SimpleItem::create($simpleItemData);
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\SimpleItem',
                                'itemable_id' => $simpleItem->id,
                                'description' => 'SimpleItem: ' . $simpleItem->description,
                                'quantity' => $simpleItem->quantity,
                                'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
                                'total_price' => $simpleItem->final_price,
                                'item_type' => 'simple'
                            ];
                        }
                        
                        // Manejar items digitales
                        elseif ($data['item_type'] === 'digital') {
                            // Asegurar que itemable_type está configurado
                            $data['itemable_type'] = 'App\\Models\\DigitalItem';
                            $digitalItem = \App\Models\DigitalItem::find($data['itemable_id']);
                            
                            if (!$digitalItem) {
                                throw new \Exception('Item digital no encontrado');
                            }
                            
                            // Preparar parámetros para cálculo
                            $params = ['quantity' => $data['quantity'] ?? 1];
                            if ($digitalItem->pricing_type === 'size') {
                                $params['width'] = $data['width'] ?? 0;
                                $params['height'] = $data['height'] ?? 0;
                            }
                            
                            // Validar parámetros
                            $errors = $digitalItem->validateParameters($params);
                            
                            if (!empty($errors)) {
                                throw new \Exception('Parámetros inválidos: ' . implode(', ', $errors));
                            }
                            
                            // Calcular precio base del item
                            $baseTotalPrice = $digitalItem->calculateTotalPrice($params);
                            
                            // Procesar acabados si existen
                            $finishingsCost = 0;
                            $finishingsData = $data['finishings'] ?? [];
                            
                            if (!empty($finishingsData)) {
                                $finishingService = app(\App\Services\FinishingCalculatorService::class);
                                
                                foreach ($finishingsData as $finishingData) {
                                    if (isset($finishingData['finishing_id']) && isset($finishingData['calculated_cost'])) {
                                        $finishingsCost += (float) $finishingData['calculated_cost'];
                                    }
                                }
                            }
                            
                            // Precio total incluyendo acabados
                            $totalPrice = $baseTotalPrice + $finishingsCost;
                            $unitPrice = $totalPrice / $params['quantity'];
                            
                            // Guardar datos de acabados para usar después de crear el DocumentItem
                            $tempFinishingsData = $finishingsData;
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\DigitalItem',
                                'itemable_id' => $digitalItem->id,
                                'description' => 'Digital: ' . $digitalItem->description . 
                                              (!empty($finishingsData) ? ' (con acabados)' : ''),
                                'quantity' => $params['quantity'],
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                                'item_type' => 'digital',
                                '_temp_finishings_data' => $tempFinishingsData // Temporal para después del guardado
                            ];
                        }
                        
                        // Manejar items personalizados
                        elseif ($data['item_type'] === 'custom' && $data['itemable_type'] === 'App\\Models\\CustomItem') {
                            // Crear el CustomItem
                            $customItem = \App\Models\CustomItem::create([
                                'description' => $data['description'],
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'total_price' => $data['quantity'] * $data['unit_price'], // Se calculará automáticamente en el modelo
                                'notes' => $data['notes'] ?? null,
                            ]);
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\CustomItem',
                                'itemable_id' => $customItem->id,
                                'description' => 'Personalizado: ' . $customItem->description,
                                'quantity' => $customItem->quantity,
                                'unit_price' => $customItem->unit_price,
                                'total_price' => $customItem->total_price,
                                'item_type' => 'custom'
                            ];
                        }
                        
                        // Manejar productos del inventario
                        elseif ($data['item_type'] === 'product' && $data['itemable_type'] === 'App\\Models\\Product') {
                            // El producto ya existe, solo necesitamos crear la referencia en DocumentItem
                            $product = \App\Models\Product::find($data['itemable_id']);
                            
                            if (!$product) {
                                throw new \Exception('Producto no encontrado');
                            }
                            
                            // Verificar stock disponible
                            $requestedQuantity = $data['quantity'];
                            if (!$product->hasStock($requestedQuantity)) {
                                throw new \Exception('Stock insuficiente. Disponible: ' . $product->stock . ', Solicitado: ' . $requestedQuantity);
                            }
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\Product',
                                'itemable_id' => $product->id,
                                'description' => 'Producto: ' . $product->name,
                                'quantity' => $requestedQuantity,
                                'unit_price' => $product->sale_price,
                                'total_price' => $product->calculateTotalPrice($requestedQuantity),
                                'item_type' => 'product'
                            ];
                        }
                        
                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        // Manejar acabados para DigitalItem si existen
                        if (isset($data['_temp_finishings_data']) && !empty($data['_temp_finishings_data'])) {
                            $digitalItem = $record->itemable;
                            
                            if ($digitalItem instanceof \App\Models\DigitalItem) {
                                foreach ($data['_temp_finishings_data'] as $finishingData) {
                                    if (isset($finishingData['finishing_id']) && 
                                        isset($finishingData['calculated_cost'])) {
                                        
                                        $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
                                        
                                        if ($finishing) {
                                            // Preparar parámetros para el acabado
                                            $finishingParams = ['quantity' => $finishingData['quantity'] ?? 1];
                                            
                                            if (isset($finishingData['width'])) {
                                                $finishingParams['width'] = $finishingData['width'];
                                            }
                                            if (isset($finishingData['height'])) {
                                                $finishingParams['height'] = $finishingData['height'];
                                            }
                                            
                                            // Agregar acabado al DigitalItem
                                            $digitalItem->addFinishing($finishing, $finishingParams);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
                    
                Action::make('quick_simple_item')
                    ->label('Item Sencillo Rápido')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Item Sencillo Rápido')
                            ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                    ])
                    ->action(function (array $data) {
                        // Crear el SimpleItem
                        $simpleItem = SimpleItem::create($data);
                        
                        // Crear el DocumentItem asociado con todos los campos requeridos
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\SimpleItem',
                            'itemable_id' => $simpleItem->id,
                            'description' => 'SimpleItem: ' . $simpleItem->description,
                            'quantity' => $simpleItem->quantity,
                            'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
                            'total_price' => $simpleItem->final_price
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('7xl'),
                    
                Action::make('quick_digital_item')
                    ->label('Item Digital Rápido')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('primary')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Agregar Item Digital')
                            ->description('Selecciona un item digital existente y especifica parámetros')
                            ->schema([
                                Select::make('digital_item_id')
                                    ->label('Item Digital')
                                    ->options(function () {
                                        return \App\Models\DigitalItem::where('active', true)
                                            ->where('company_id', auth()->user()->company_id)
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                return [
                                                    $item->id => $item->code . ' - ' . $item->description . ' (' . $item->pricing_type_name . ')'
                                                ];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $item = \App\Models\DigitalItem::find($state);
                                            if ($item) {
                                                $set('item_description', $item->description);
                                                $set('pricing_type', $item->pricing_type);
                                                $set('unit_value', $item->unit_value);
                                            }
                                        }
                                    }),
                                    
                                \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('unidades')
                                            ->live(),
                                            
                                        Forms\Components\TextInput::make('width')
                                            ->label('Ancho (cm)')
                                            ->numeric()
                                            ->visible(fn ($get) => $get('pricing_type') === 'size')
                                            ->required(fn ($get) => $get('pricing_type') === 'size')
                                            ->live(),
                                            
                                        Forms\Components\TextInput::make('height')
                                            ->label('Alto (cm)')
                                            ->numeric()
                                            ->visible(fn ($get) => $get('pricing_type') === 'size')
                                            ->required(fn ($get) => $get('pricing_type') === 'size')
                                            ->live(),
                                    ]),
                                    
                                Forms\Components\Placeholder::make('digital_calc')
                                    ->content(function ($get) {
                                        $itemId = $get('digital_item_id');
                                        $quantity = $get('quantity') ?? 1;
                                        $width = $get('width') ?? 0;
                                        $height = $get('height') ?? 0;
                                        
                                        if (!$itemId) {
                                            return '📋 Selecciona un item digital para ver el cálculo';
                                        }
                                        
                                        $item = \App\Models\DigitalItem::find($itemId);
                                        if (!$item) {
                                            return '❌ Item digital no encontrado';
                                        }
                                        
                                        $params = ['quantity' => $quantity];
                                        
                                        if ($item->pricing_type === 'size') {
                                            $params['width'] = $width;
                                            $params['height'] = $height;
                                        }
                                        
                                        $errors = $item->validateParameters($params);
                                        
                                        $content = '<div class="space-y-2">';
                                        $content .= '<div><strong>📋 Item:</strong> ' . $item->description . '</div>';
                                        $content .= '<div><strong>📐 Tipo:</strong> ' . $item->pricing_type_name . '</div>';
                                        $content .= '<div><strong>💰 Valor unitario:</strong> ' . $item->formatted_unit_value . '</div>';
                                        
                                        if (!empty($errors)) {
                                            $content .= '<div class="text-red-600 mt-2">';
                                            foreach ($errors as $error) {
                                                $content .= '<div>❌ ' . $error . '</div>';
                                            }
                                            $content .= '</div>';
                                        } else {
                                            $totalPrice = $item->calculateTotalPrice($params);
                                            $unitPrice = $totalPrice / $quantity;
                                            
                                            if ($item->pricing_type === 'size' && $width > 0 && $height > 0) {
                                                $area = ($width / 100) * ($height / 100); // Convertir a m²
                                                $content .= '<div><strong>📏 Área:</strong> ' . number_format($area, 4) . ' m²</div>';
                                            }
                                            
                                            $content .= '<div class="mt-2 p-2 bg-blue-50 rounded">';
                                            $content .= '<div><strong>💵 Precio por unidad:</strong> $' . number_format($unitPrice, 2) . '</div>';
                                            $content .= '<div><strong>💵 Total:</strong> $' . number_format($totalPrice, 2) . '</div>';
                                            $content .= '</div>';
                                            
                                            $content .= '<div class="text-green-600"><strong>✅ Cálculo válido</strong></div>';
                                        }
                                        
                                        $content .= '</div>';
                                        return $content;
                                    })
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn ($get) => filled($get('digital_item_id'))),
                                    
                                Forms\Components\Hidden::make('item_description'),
                                Forms\Components\Hidden::make('pricing_type'),
                                Forms\Components\Hidden::make('unit_value'),
                            ])
                    ])
                    ->action(function (array $data) {
                        $digitalItem = \App\Models\DigitalItem::find($data['digital_item_id']);
                        
                        if (!$digitalItem) {
                            throw new \Exception('Item digital no encontrado');
                        }
                        
                        // Preparar parámetros para cálculo
                        $params = ['quantity' => $data['quantity'] ?? 1];
                        if ($digitalItem->pricing_type === 'size') {
                            $params['width'] = $data['width'] ?? 0;
                            $params['height'] = $data['height'] ?? 0;
                        }
                        
                        // Validar parámetros
                        $errors = $digitalItem->validateParameters($params);
                        
                        if (!empty($errors)) {
                            throw new \Exception('Parámetros inválidos: ' . implode(', ', $errors));
                        }
                        
                        // Calcular precio total
                        $totalPrice = $digitalItem->calculateTotalPrice($params);
                        $unitPrice = $totalPrice / $params['quantity'];
                        
                        // Crear el DocumentItem asociado con item_config
                        $itemConfig = [
                            'pricing_type' => $digitalItem->pricing_type,
                            'unit_value' => $digitalItem->unit_value
                        ];
                        
                        if ($digitalItem->pricing_type === 'size') {
                            $itemConfig['width'] = $params['width'];
                            $itemConfig['height'] = $params['height'];
                        }
                        
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\DigitalItem',
                            'itemable_id' => $digitalItem->id,
                            'description' => 'Digital: ' . $digitalItem->description,
                            'quantity' => $params['quantity'],
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'item_type' => 'digital',
                            'item_config' => json_encode($itemConfig)
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('5xl')
                    ->successNotificationTitle('Item digital agregado correctamente'),
                    
                Action::make('quick_product_item')
                    ->label('Producto Rápido')
                    ->icon('heroicon-o-cube')
                    ->color('purple')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Agregar Producto del Inventario')
                            ->description('Selecciona un producto existente y especifica la cantidad')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->options(function () {
                                        return \App\Models\Product::where('active', true)
                                            ->where('company_id', auth()->user()->company_id)
                                            ->get()
                                            ->mapWithKeys(function ($product) {
                                                $stockStatus = $product->stock == 0 ? ' (SIN STOCK)' : 
                                                              ($product->isLowStock() ? ' (STOCK BAJO)' : '');
                                                return [
                                                    $product->id => $product->name . ' - $' . number_format($product->sale_price, 2) . 
                                                                   ' (Stock: ' . $product->stock . ')' . $stockStatus
                                                ];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->sale_price);
                                                $set('product_name', $product->name);
                                                $set('available_stock', $product->stock);
                                            }
                                        }
                                    }),
                                    
                                \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('unidades')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $productId = $get('product_id');
                                                if ($productId && $state) {
                                                    $product = \App\Models\Product::find($productId);
                                                    if ($product) {
                                                        $total = $product->sale_price * $state;
                                                        $set('total_price', $total);
                                                    }
                                                }
                                            }),
                                            
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Precio Unitario')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated(false),
                                            
                                        Forms\Components\TextInput::make('total_price')
                                            ->label('Precio Total')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                    
                                Forms\Components\Placeholder::make('stock_info')
                                    ->content(function ($get) {
                                        $productId = $get('product_id');
                                        $quantity = $get('quantity') ?? 0;
                                        
                                        if (!$productId) {
                                            return '📦 Selecciona un producto para ver la información de stock';
                                        }
                                        
                                        $product = \App\Models\Product::find($productId);
                                        if (!$product) {
                                            return '❌ Producto no encontrado';
                                        }
                                        
                                        $content = '<div class="space-y-2">';
                                        $content .= '<div><strong>📋 Producto:</strong> ' . $product->name . '</div>';
                                        $content .= '<div><strong>💰 Precio:</strong> $' . number_format($product->sale_price, 2) . '</div>';
                                        $content .= '<div><strong>📦 Stock disponible:</strong> ' . $product->stock . ' unidades</div>';
                                        
                                        if ($quantity > 0) {
                                            if ($product->stock == 0) {
                                                $content .= '<div class="text-red-600"><strong>🔴 Sin stock</strong> - No hay unidades disponibles</div>';
                                            } elseif ($quantity > $product->stock) {
                                                $content .= '<div class="text-red-600"><strong>⚠️ Stock insuficiente</strong> - Solo hay ' . $product->stock . ' unidades</div>';
                                            } elseif ($product->stock - $quantity <= $product->min_stock) {
                                                $remaining = $product->stock - $quantity;
                                                $content .= '<div class="text-yellow-600"><strong>🟡 Advertencia:</strong> Quedarán ' . $remaining . ' unidades (stock bajo)</div>';
                                            } else {
                                                $content .= '<div class="text-green-600"><strong>✅ Stock suficiente</strong></div>';
                                            }
                                            
                                            $totalPrice = $product->sale_price * $quantity;
                                            $content .= '<div class="mt-2 p-2 bg-blue-50 rounded"><strong>💵 Total:</strong> $' . number_format($totalPrice, 2) . '</div>';
                                        }
                                        
                                        $content .= '</div>';
                                        return $content;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                                    
                                Forms\Components\Hidden::make('product_name'),
                                Forms\Components\Hidden::make('available_stock'),
                            ])
                    ])
                    ->action(function (array $data) {
                        $product = \App\Models\Product::find($data['product_id']);
                        
                        if (!$product) {
                            throw new \Exception('Producto no encontrado');
                        }
                        
                        $quantity = $data['quantity'];
                        
                        // Verificar stock disponible
                        if (!$product->hasStock($quantity)) {
                            throw new \Exception('Stock insuficiente. Disponible: ' . $product->stock . ', Solicitado: ' . $quantity);
                        }
                        
                        // Crear el DocumentItem asociado
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\Product',
                            'itemable_id' => $product->id,
                            'description' => 'Producto: ' . $product->name,
                            'quantity' => $quantity,
                            'unit_price' => $product->sale_price,
                            'total_price' => $product->calculateTotalPrice($quantity)
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('5xl')
                    ->successNotificationTitle('Producto agregado correctamente'),
                    
                Action::make('quick_magazine_item')
                    ->label('Revista Rápida')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('indigo')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Crear Revista')
                            ->description('Crear una nueva revista con configuración básica')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción de la Revista')
                                    ->required()
                                    ->rows(2)
                                    ->placeholder('Revista corporativa, catálogo de productos, etc.'),
                                    
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(100)
                                            ->minValue(1)
                                            ->suffix('revistas'),
                                            
                                        Forms\Components\TextInput::make('closed_width')
                                            ->label('Ancho Cerrado (cm)')
                                            ->numeric()
                                            ->required()
                                            ->default(21)
                                            ->minValue(1)
                                            ->suffix('cm'),
                                            
                                        Forms\Components\TextInput::make('closed_height')
                                            ->label('Alto Cerrado (cm)')
                                            ->numeric()
                                            ->required()
                                            ->default(29.7)
                                            ->minValue(1)
                                            ->suffix('cm'),
                                    ]),
                                    
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('binding_type')
                                            ->label('Tipo de Encuadernación')
                                            ->required()
                                            ->options([
                                                'grapado' => 'Grapado',
                                                'cosido' => 'Cosido',
                                                'anillado' => 'Anillado',
                                                'espiral' => 'Espiral',
                                            ])
                                            ->default('grapado'),
                                            
                                        Forms\Components\Select::make('binding_side')
                                            ->label('Lado de Encuadernación')
                                            ->required()
                                            ->options([
                                                'izquierda' => 'Izquierda',
                                                'derecha' => 'Derecha',
                                                'arriba' => 'Arriba',
                                                'abajo' => 'Abajo',
                                            ])
                                            ->default('izquierda'),
                                    ]),
                                    
                                Forms\Components\Placeholder::make('magazine_info')
                                    ->content('📖 Una vez creada la revista, podrás agregar las páginas (SimpleItems) desde la vista de edición.')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (array $data) {
                        // Crear el MagazineItem
                        $magazine = \App\Models\MagazineItem::create([
                            'description' => $data['description'],
                            'quantity' => $data['quantity'],
                            'closed_width' => $data['closed_width'],
                            'closed_height' => $data['closed_height'],
                            'binding_type' => $data['binding_type'],
                            'binding_side' => $data['binding_side'],
                            'design_value' => 0,
                            'transport_value' => 0,
                            'profit_percentage' => 25,
                        ]);
                        
                        // Crear el DocumentItem asociado
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\MagazineItem',
                            'itemable_id' => $magazine->id,
                            'description' => 'Revista: ' . $magazine->description,
                            'quantity' => $magazine->quantity,
                            'unit_price' => $magazine->final_price > 0 ? $magazine->final_price / $magazine->quantity : 0,
                            'total_price' => $magazine->final_price
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('4xl')
                    ->successNotificationTitle('Revista creada correctamente'),
                    
                Action::make('quick_talonario_item')
                    ->label('Talonario Rápido')
                    ->icon('heroicon-o-document-check')
                    ->color('warning')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Crear Talonario')
                            ->description('Crear un nuevo talonario con configuración básica')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción del Talonario')
                                    ->required()
                                    ->rows(2)
                                    ->placeholder('Facturas, recibos, remisiones, etc.'),
                                    
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad de Talonarios')
                                            ->numeric()
                                            ->required()
                                            ->default(10)
                                            ->minValue(1)
                                            ->suffix('talonarios'),
                                            
                                        Forms\Components\TextInput::make('numbers_per_booklet')
                                            ->label('Números por Talonario')
                                            ->numeric()
                                            ->required()
                                            ->default(100)
                                            ->minValue(1)
                                            ->suffix('números'),
                                            
                                        Forms\Components\TextInput::make('ancho')
                                            ->label('Ancho (cm)')
                                            ->numeric()
                                            ->required()
                                            ->default(21)
                                            ->minValue(1)
                                            ->suffix('cm'),
                                            
                                        Forms\Components\TextInput::make('alto')
                                            ->label('Alto (cm)')
                                            ->numeric()
                                            ->required()
                                            ->default(29.7)
                                            ->minValue(1)
                                            ->suffix('cm'),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Numeración')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('prefix')
                                                    ->label('Prefijo')
                                                    ->placeholder('FAC, REC, REM')
                                                    ->maxLength(10),
                                                    
                                                Forms\Components\TextInput::make('start_number')
                                                    ->label('Número Inicial')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->minValue(1),
                                                    
                                                Forms\Components\TextInput::make('end_number')
                                                    ->label('Número Final')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(100)
                                                    ->minValue(1),
                                            ]),
                                    ])
                                    ->collapsible(),
                                    
                                \Filament\Schemas\Components\Section::make('Acabados Rápidos')
                                    ->description('Acabados comunes para talonarios')
                                    ->schema([
                                        Forms\Components\Checkbox::make('include_numbering')
                                            ->label('Incluir Numeración')
                                            ->default(true)
                                            ->helperText('Agrega el costo de numeración automáticamente'),
                                            
                                        Forms\Components\Checkbox::make('include_perforation')
                                            ->label('Incluir Perforación')
                                            ->default(false)
                                            ->helperText('Agrega perforación para desprendimiento fácil'),
                                            
                                        Forms\Components\Checkbox::make('include_gluing')
                                            ->label('Incluir Engomado')
                                            ->default(false)
                                            ->helperText('Agrega engomado en el lomo del talonario'),
                                    ])
                                    ->collapsible(),
                                    
                                Forms\Components\Placeholder::make('talonario_preview')
                                    ->content(function ($get) {
                                        $quantity = $get('quantity') ?? 10;
                                        $numbersPerBooklet = $get('numbers_per_booklet') ?? 100;
                                        $startNumber = $get('start_number') ?? 1;
                                        $prefix = $get('prefix') ?: 'SIN PREFIJO';
                                        
                                        $totalNumbers = $quantity * $numbersPerBooklet;
                                        
                                        $preview = '<div class="p-4 bg-blue-50 rounded space-y-2">';
                                        $preview .= '<h4 class="font-semibold text-blue-800">Vista Previa del Talonario</h4>';
                                        $preview .= '<div><strong>📋 Cantidad:</strong> ' . $quantity . ' talonarios</div>';
                                        $preview .= '<div><strong>🔢 Números por talonario:</strong> ' . $numbersPerBooklet . '</div>';
                                        $preview .= '<div><strong>📊 Total de números:</strong> ' . number_format($totalNumbers) . '</div>';
                                        $preview .= '<div><strong>🏷️ Rango:</strong> ' . $prefix . '-' . str_pad($startNumber, 3, '0', STR_PAD_LEFT) . ' hasta ' . $prefix . '-' . str_pad($startNumber + $totalNumbers - 1, 3, '0', STR_PAD_LEFT) . '</div>';
                                        $preview .= '</div>';
                                        
                                        return $preview;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (array $data) {
                        // Crear el TalonarioItem con campos en español
                        $talonario = \App\Models\TalonarioItem::create([
                            'description' => $data['description'],
                            'quantity' => $data['quantity'],
                            'numeros_por_talonario' => $data['numbers_per_booklet'],
                            'prefijo' => $data['prefix'] ?? '',
                            'numero_inicial' => $data['start_number'],
                            'numero_final' => $data['end_number'],
                            'ancho' => $data['ancho'],
                            'alto' => $data['alto'],
                            'design_value' => 0,
                            'transport_value' => 0,
                            'profit_percentage' => 25,
                        ]);
                        
                        // Crear una hoja básica para el talonario con el papel por defecto
                        $defaultPaper = \App\Models\Paper::where('company_id', auth()->user()->company_id)->first();
                        $defaultMachine = \App\Models\PrintingMachine::where('company_id', auth()->user()->company_id)->first();
                        
                        if ($defaultPaper && $defaultMachine) {
                            // Calcular cantidad total de talonarios que se van a producir
                            $totalNumbers = ($data['end_number'] - $data['start_number']) + 1;
                            $totalTalonarios = ceil($totalNumbers / $data['numbers_per_booklet']);
                            $finalQuantity = $data['quantity']; // Cantidad solicitada por el cliente
                            
                            $simpleItem = \App\Models\SimpleItem::create([
                                'description' => 'Hoja básica para ' . $data['description'],
                                'quantity' => $finalQuantity,
                                'horizontal_size' => $data['ancho'],
                                'vertical_size' => $data['alto'],
                                'paper_id' => $defaultPaper->id,
                                'printing_machine_id' => $defaultMachine->id,
                                'ink_front_count' => 1,
                                'ink_back_count' => 0,
                                'front_back_plate' => false,
                                'design_value' => 0,
                                'transport_value' => 0,
                                'rifle_value' => 0,
                                'profit_percentage' => 0, // No aplicar ganancia doble
                            ]);
                            
                            // Vincular la hoja al talonario
                            \App\Models\TalonarioSheet::create([
                                'talonario_item_id' => $talonario->id,
                                'simple_item_id' => $simpleItem->id,
                                'sheet_type' => 'original',
                                'sheet_order' => 1,
                                'paper_color' => 'blanco',
                                'sheet_notes' => 'Hoja básica del talonario',
                            ]);
                        }
                        
                        // Agregar acabados automáticamente si están seleccionados
                        if ($data['include_numbering'] ?? false) {
                            $numberingFinishing = \App\Models\Finishing::where('company_id', auth()->user()->company_id)
                                ->where('name', 'LIKE', '%numeración%')
                                ->orWhere('name', 'LIKE', '%numeracion%')
                                ->first();
                                
                            if ($numberingFinishing) {
                                $totalNumbers = ($data['end_number'] - $data['start_number']) + 1;
                                $unitCost = $numberingFinishing->unit_price ?? 0;
                                $talonario->finishings()->attach($numberingFinishing->id, [
                                    'quantity' => $totalNumbers,
                                    'unit_cost' => $unitCost,
                                    'total_cost' => $totalNumbers * $unitCost,
                                ]);
                            }
                        }
                        
                        if ($data['include_perforation'] ?? false) {
                            $perforationFinishing = \App\Models\Finishing::where('company_id', auth()->user()->company_id)
                                ->where('name', 'LIKE', '%perforación%')
                                ->orWhere('name', 'LIKE', '%perforacion%')
                                ->first();
                                
                            if ($perforationFinishing) {
                                $totalNumbers = ($data['end_number'] - $data['start_number']) + 1;
                                $totalTalonarios = ceil($totalNumbers / $data['numbers_per_booklet']);
                                $unitCost = $perforationFinishing->unit_price ?? 0;
                                $talonario->finishings()->attach($perforationFinishing->id, [
                                    'quantity' => $totalTalonarios, // Perforación por talonario
                                    'unit_cost' => $unitCost,
                                    'total_cost' => $totalTalonarios * $unitCost,
                                ]);
                            }
                        }
                        
                        if ($data['include_gluing'] ?? false) {
                            $gluingFinishing = \App\Models\Finishing::where('company_id', auth()->user()->company_id)
                                ->where('name', 'LIKE', '%engomado%')
                                ->first();
                                
                            if ($gluingFinishing) {
                                $totalNumbers = ($data['end_number'] - $data['start_number']) + 1;
                                $totalTalonarios = ceil($totalNumbers / $data['numbers_per_booklet']);
                                $unitCost = $gluingFinishing->unit_price ?? 0;
                                $talonario->finishings()->attach($gluingFinishing->id, [
                                    'quantity' => $totalTalonarios, // Engomado por talonario
                                    'unit_cost' => $unitCost,
                                    'total_cost' => $totalTalonarios * $unitCost,
                                ]);
                            }
                        }
                        
                        // Recargar relaciones y recalcular precios del talonario
                        $talonario->load(['sheets.simpleItem', 'finishings']);
                        $talonario->calculateAll();
                        $talonario->save();
                        
                        // Crear el DocumentItem asociado con precios correctos
                        $unitPrice = $talonario->quantity > 0 ? $talonario->final_price / $talonario->quantity : 0;
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\TalonarioItem',
                            'itemable_id' => $talonario->id,
                            'description' => 'Talonario: ' . $talonario->description,
                            'quantity' => $talonario->quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $talonario->final_price
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('5xl')
                    ->successNotificationTitle('Talonario creado correctamente'),
                    
                Action::make('quick_custom_item')
                    ->label('Item Personalizado Rápido')
                    ->icon('heroicon-o-pencil-square')
                    ->color('secondary')
                    ->form([
                        \Filament\Schemas\Components\Section::make('Crear Item Personalizado')
                            ->description('Agrega un item con precios manuales sin cálculos automáticos')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción del Item')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Describe el producto o servicio personalizado')
                                    ->columnSpanFull(),
                                    
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('unidades')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $total = $state * $unitPrice;
                                                $set('total_price', number_format($total, 2, '.', ''));
                                            }),
                                            
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Precio Unitario')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $quantity = $get('quantity') ?? 1;
                                                $total = $quantity * $state;
                                                $set('total_price', number_format($total, 2, '.', ''));
                                            }),
                                            
                                        Forms\Components\TextInput::make('total_price')
                                            ->label('Precio Total')
                                            ->numeric()
                                            ->prefix('$')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                    
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas Adicionales')
                                    ->rows(2)
                                    ->placeholder('Notas internas sobre este item (opcional)')
                                    ->columnSpanFull(),
                                    
                                Forms\Components\Placeholder::make('custom_summary')
                                    ->content(function ($get) {
                                        $description = $get('description');
                                        $quantity = $get('quantity') ?? 1;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $totalPrice = $quantity * $unitPrice;
                                        
                                        if (empty($description)) {
                                            return '<div class="p-3 bg-gray-50 rounded text-gray-500">📝 Completa la descripción para ver el resumen</div>';
                                        }
                                        
                                        $content = '<div class="p-4 bg-green-50 rounded space-y-2">';
                                        $content .= '<h4 class="font-semibold text-green-800">📋 Resumen del Item</h4>';
                                        $content .= '<div class="space-y-1 text-sm">';
                                        $content .= '<div><strong>Descripción:</strong> ' . e(substr($description, 0, 80)) . (strlen($description) > 80 ? '...' : '') . '</div>';
                                        $content .= '<div><strong>Cantidad:</strong> ' . number_format($quantity) . ' unidades</div>';
                                        $content .= '<div><strong>Precio unitario:</strong> $' . number_format($unitPrice, 2) . '</div>';
                                        $content .= '</div>';
                                        $content .= '<div class="mt-3 p-3 bg-white rounded border border-green-200">';
                                        $content .= '<div class="text-lg font-bold text-green-600 text-center">';
                                        $content .= '💵 TOTAL: $' . number_format($totalPrice, 2);
                                        $content .= '</div>';
                                        $content .= '</div>';
                                        $content .= '</div>';
                                        
                                        return $content;
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (array $data) {
                        // Crear el CustomItem
                        $customItem = \App\Models\CustomItem::create([
                            'description' => $data['description'],
                            'quantity' => $data['quantity'],
                            'unit_price' => $data['unit_price'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                        
                        // Crear el DocumentItem asociado
                        $this->getOwnerRecord()->items()->create([
                            'itemable_type' => 'App\\Models\\CustomItem',
                            'itemable_id' => $customItem->id,
                            'description' => 'Personalizado: ' . $customItem->description,
                            'quantity' => $customItem->quantity,
                            'unit_price' => $customItem->unit_price,
                            'total_price' => $customItem->total_price
                        ]);
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        // Refrescar la tabla
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('4xl')
                    ->successNotificationTitle('Item personalizado creado correctamente'),
            ])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn ($record) => $record->itemable !== null)
                    ->form(function ($record) {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                            return [
                                \Filament\Schemas\Components\Section::make('Editar Item Sencillo')
                                    ->description('Modificar los detalles del item y recalcular automáticamente')
                                    ->schema(SimpleItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                            ];
                        }
                        
                        if ($record->itemable_type === 'App\\Models\\MagazineItem') {
                            return [
                                \Filament\Schemas\Components\Section::make('Editar Revista')
                                    ->description('Modificar los detalles de la revista y recalcular automáticamente')
                                    ->schema(\App\Filament\Resources\MagazineItems\Schemas\MagazineItemForm::configure(new \Filament\Schemas\Schema())->getComponents())
                            ];
                        }
                        
                        if ($record->itemable_type === 'App\\Models\\CustomItem') {
                            return [
                                \Filament\Schemas\Components\Section::make('Editar Item Personalizado')
                                    ->description('Modificar los detalles del item personalizado')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción del Item')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),
                                            
                                        Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->suffix('unidades')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $unitPrice = $get('unit_price') ?? 0;
                                                        $total = $state * $unitPrice;
                                                        $set('calculated_total', number_format($total, 2));
                                                    }),
                                                    
                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Precio Unitario')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $total = $quantity * $state;
                                                        $set('calculated_total', number_format($total, 2));
                                                    }),
                                                    
                                                Forms\Components\Placeholder::make('calculated_total')
                                                    ->label('Total Calculado')
                                                    ->content(function ($get) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $unitPrice = $get('unit_price') ?? 0;
                                                        return '$' . number_format($quantity * $unitPrice, 2);
                                                    }),
                                            ]),
                                            
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notas Adicionales')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                            ];
                        }
                        
                        // TalonarioItem - Con gestión de hojas
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem') {
                            return [
                                \Filament\Schemas\Components\Section::make('Información Básica')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción del Talonario')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Ej: Recibos de caja, Facturas comerciales, Remisiones de entrega...'),
                                            
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Cantidad de Talonarios')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(10)
                                                    ->minValue(1)
                                                    ->suffix('talonarios'),
                                                    
                                                Forms\Components\TextInput::make('profit_percentage')
                                                    ->label('Margen de Ganancia')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(25)
                                                    ->minValue(0)
                                                    ->maxValue(500)
                                                    ->suffix('%'),
                                            ]),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Configuración de Numeración')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('prefijo')
                                                    ->label('Prefijo')
                                                    ->default('Nº')
                                                    ->maxLength(10)
                                                    ->placeholder('Nº, Rec., Fact., Rem.')
                                                    ->helperText('Prefijo que aparece antes del número'),
                                                    
                                                Forms\Components\TextInput::make('numero_inicial')
                                                    ->label('Número Inicial')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->minValue(1),
                                                    
                                                Forms\Components\TextInput::make('numero_final')
                                                    ->label('Número Final')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1000)
                                                    ->minValue(2),
                                            ]),
                                            
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('numeros_por_talonario')
                                                    ->label('Números por Talonario')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(25)
                                                    ->minValue(1)
                                                    ->maxValue(100)
                                                    ->helperText('Cantidad de números en cada talonario'),
                                                    
                                                Forms\Components\Placeholder::make('numbering_preview')
                                                    ->label('Vista Previa')
                                                    ->content(function ($get, $record) {
                                                        if (!$record || !$record->itemable) return '📋 Guardando...';
                                                        
                                                        $prefijo = $get('prefijo') ?? $record->itemable->prefijo ?? 'Nº';
                                                        $inicial = $get('numero_inicial') ?? $record->itemable->numero_inicial ?? 1;
                                                        $final = $get('numero_final') ?? $record->itemable->numero_final ?? 1000;
                                                        
                                                        if ($final <= $inicial) {
                                                            return '⚠️ El número final debe ser mayor al inicial';
                                                        }
                                                        
                                                        $totalNumbers = ($final - $inicial) + 1;
                                                        $numerosporTalonario = $get('numeros_por_talonario') ?? $record->itemable->numeros_por_talonario ?? 25;
                                                        $totalTalonarios = ceil($totalNumbers / $numerosporTalonario);
                                                        
                                                        return "📊 Rango: Del {$prefijo}{$inicial} al {$prefijo}{$final}<br>" .
                                                               "📈 Total: {$totalNumbers} números • {$totalTalonarios} talonarios";
                                                    })
                                                    ->html(),
                                            ]),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Dimensiones del Talonario')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('ancho')
                                                    ->label('Ancho')
                                                    ->numeric()
                                                    ->required()
                                                    ->suffix('cm')
                                                    ->minValue(0)
                                                    ->step(0.1)
                                                    ->helperText('Ancho del talonario completo'),
                                                    
                                                Forms\Components\TextInput::make('alto')
                                                    ->label('Alto')
                                                    ->numeric()
                                                    ->required()
                                                    ->suffix('cm')
                                                    ->minValue(0)
                                                    ->step(0.1)
                                                    ->helperText('Alto del talonario completo'),
                                            ]),
                                            
                                        Forms\Components\Placeholder::make('dimensions_preview')
                                            ->label('Área Total')
                                            ->content(function ($get, $record) {
                                                if (!$record || !$record->itemable) return '📐 Guardando...';
                                                
                                                $ancho = $get('ancho') ?? $record->itemable->ancho ?? 0;
                                                $alto = $get('alto') ?? $record->itemable->alto ?? 0;
                                                
                                                if ($ancho > 0 && $alto > 0) {
                                                    $area = $ancho * $alto;
                                                    return "📐 Dimensiones: {$ancho}cm × {$alto}cm = {$area}cm²";
                                                }
                                                
                                                return '📐 Ingrese las dimensiones para ver el área';
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Hojas del Talonario')
                                    ->description('Gestión de hojas que componen el talonario')
                                    ->schema([
                                        Forms\Components\Placeholder::make('existing_sheets_table')
                                            ->label('Hojas Actuales')
                                            ->content(function ($record) {
                                                if (!$record || !$record->itemable) {
                                                    return '📋 Guardando talonario...';
                                                }

                                                $sheets = $record->itemable->getSheetsTableData();
                                                
                                                if (empty($sheets)) {
                                                    return '📋 No hay hojas agregadas. Use el botón "Agregar Hoja" para crear la primera hoja.';
                                                }

                                                $content = '<div class="overflow-x-auto">';
                                                $content .= '<table class="min-w-full divide-y divide-gray-200">';
                                                $content .= '<thead class="bg-gray-50">';
                                                $content .= '<tr>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Color</th>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>';
                                                $content .= '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>';
                                                $content .= '</tr>';
                                                $content .= '</thead>';
                                                $content .= '<tbody class="bg-white divide-y divide-gray-200">';

                                                foreach ($sheets as $sheet) {
                                                    $content .= '<tr>';
                                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">' . $sheet['order'] . '</td>';
                                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">';
                                                    $content .= '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">' . $sheet['type'] . '</span>';
                                                    $content .= '</td>';
                                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">';
                                                    $content .= '<span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">' . ucfirst($sheet['color']) . '</span>';
                                                    $content .= '</td>';
                                                    $content .= '<td class="px-3 py-2 text-sm text-gray-900">' . substr($sheet['description'], 0, 50) . '...</td>';
                                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">$' . $sheet['unit_price'] . '</td>';
                                                    $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">$' . $sheet['total_cost'] . '</td>';
                                                    $content .= '</tr>';
                                                }

                                                $content .= '</tbody>';
                                                $content .= '</table>';
                                                $content .= '</div>';

                                                return $content;
                                            })
                                            ->html()
                                            ->columnSpanFull(),
                                            
                                        Forms\Components\Placeholder::make('sheet_management_info')
                                            ->label('Gestión de Hojas')
                                            ->content(function ($record) {
                                                if (!$record || !$record->itemable) {
                                                    return '📋 Guarde el talonario para gestionar hojas';
                                                }
                                                
                                                return '✅ Use el botón verde ➕ en la esquina superior derecha para agregar hojas';
                                            })
                                            ->columnSpanFull(),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Acabados Específicos')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('finishing_ids')
                                            ->label('Acabados para Talonarios')
                                            ->options(function () {
                                                return \App\Models\Finishing::query()
                                                    ->where('active', true)
                                                    ->whereIn('measurement_unit', [
                                                        \App\Enums\FinishingMeasurementUnit::POR_NUMERO->value,
                                                        \App\Enums\FinishingMeasurementUnit::POR_TALONARIO->value
                                                    ])
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->descriptions(function () {
                                                return \App\Models\Finishing::query()
                                                    ->where('active', true)
                                                    ->whereIn('measurement_unit', [
                                                        \App\Enums\FinishingMeasurementUnit::POR_NUMERO->value,
                                                        \App\Enums\FinishingMeasurementUnit::POR_TALONARIO->value
                                                    ])
                                                    ->get()
                                                    ->mapWithKeys(function ($finishing) {
                                                        $description = $finishing->description;
                                                        $price = '$' . number_format($finishing->unit_price, 0);
                                                        $unit = $finishing->measurement_unit->label();
                                                        return [$finishing->id => "{$description} - {$price} {$unit}"];
                                                    })
                                                    ->toArray();
                                            })
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->helperText('Seleccione los acabados específicos que requiere el talonario')
                                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, $state, $record) {
                                                if ($record && $record->itemable) {
                                                    $finishingIds = $record->itemable->finishings()->pluck('finishings.id')->toArray();
                                                    $component->state($finishingIds);
                                                }
                                            }),
                                    ]),
                                    
                                \Filament\Schemas\Components\Section::make('Costos Adicionales')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('design_value')
                                                    ->label('Valor Diseño')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->prefix('$'),
                                                    
                                                Forms\Components\TextInput::make('transport_value')
                                                    ->label('Valor Transporte')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->prefix('$'),
                                            ]),
                                    ]),
                                    
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas Adicionales')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ];
                        }
                        
                        // Para otros tipos de items, mostrar formulario básico
                        return [
                            \Filament\Schemas\Components\Section::make('Editar Item - ' . class_basename($record->itemable_type))
                                ->description('Este tipo de item tiene opciones de edición limitadas')
                                ->schema([
                                    Forms\Components\Textarea::make('description')
                                        ->label('Descripción')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),
                                        
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),
                                        
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Precio Unitario')
                                        ->numeric()
                                        ->required()
                                        ->prefix('$')
                                        ->step(0.01),
                                        
                                    Forms\Components\Placeholder::make('total_preview')
                                        ->label('Total Calculado')
                                        ->content(function ($get) {
                                            $qty = $get('quantity') ?? 0;
                                            $price = $get('unit_price') ?? 0;
                                            return '$' . number_format($qty * $price, 2);
                                        }),
                                ])
                        ];
                    })
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable) {
                            // Cargar todos los datos del SimpleItem para mostrar en el formulario
                            $simpleItemData = $record->itemable->toArray();
                            
                            // Agregar campos adicionales que no están en la tabla
                            $simpleItemData['item_type'] = 'simple';
                            $simpleItemData['itemable_type'] = $record->itemable_type;
                            $simpleItemData['itemable_id'] = $record->itemable_id;
                            
                            return $simpleItemData;
                        }
                        
                        if ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable) {
                            // Cargar todos los datos del MagazineItem para mostrar en el formulario
                            $magazineData = $record->itemable->toArray();
                            
                            // Cargar relaciones necesarias
                            $record->itemable->load(['pages.simpleItem', 'finishings']);
                            
                            return $magazineData;
                        }
                        
                        if ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            // Cargar todos los datos del CustomItem para mostrar en el formulario
                            return $record->itemable->toArray();
                        }
                        
                        // Para TalonarioItems, cargar todos los datos existentes
                        if ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable) {
                            $talonario = $record->itemable;
                            
                            // Cargar acabados existentes
                            $finishingIds = $talonario->finishings()->pluck('finishings.id')->toArray();
                            
                            return [
                                'item_type' => 'talonario',
                                'itemable_type' => $record->itemable_type,
                                'itemable_id' => $record->itemable_id,
                                'description' => $talonario->description,
                                'quantity' => $talonario->quantity,
                                'profit_percentage' => $talonario->profit_percentage,
                                'prefijo' => $talonario->prefijo ?? 'Nº',
                                'numero_inicial' => $talonario->numero_inicial,
                                'numero_final' => $talonario->numero_final,
                                'numeros_por_talonario' => $talonario->numeros_por_talonario,
                                'ancho' => $talonario->ancho,
                                'alto' => $talonario->alto,
                                'finishing_ids' => $finishingIds,
                                'design_value' => $talonario->design_value ?? 0,
                                'transport_value' => $talonario->transport_value ?? 0,
                                'notes' => $talonario->notes,
                            ];
                        }
                        
                        // Para DigitalItems, cargar acabados existentes
                        if ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            $digitalItem = $record->itemable;
                            
                            // Cargar acabados existentes
                            $finishingsData = [];
                            $existingFinishings = $digitalItem->finishings()->get();
                            
                            foreach ($existingFinishings as $finishing) {
                                $finishingsData[] = [
                                    'finishing_id' => $finishing->id,
                                    'quantity' => $finishing->pivot->quantity ?? 1,
                                    'width' => $finishing->pivot->width,
                                    'height' => $finishing->pivot->height,
                                    'calculated_cost' => $finishing->pivot->calculated_cost,
                                ];
                            }
                            
                            return [
                                'item_type' => 'digital',
                                'itemable_type' => $record->itemable_type,
                                'itemable_id' => $record->itemable_id,
                                'quantity' => $record->quantity,
                                'finishings' => $finishingsData,
                                'unit_price' => $record->unit_price,
                                'total_price' => $record->total_price,
                            ];
                        }
                        
                        // Para otros tipos, usar datos del DocumentItem
                        return [
                            'description' => $record->itemable ? $record->itemable->description : $record->description,
                            'quantity' => $record->quantity,
                            'unit_price' => $record->unit_price,
                        ];
                    })
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        if ($record->itemable_type === 'App\\Models\\SimpleItem' && $record->itemable) {
                            // Asegurar que el itemable está cargado correctamente
                            $record->load('itemable');
                            $simpleItem = $record->itemable;
                            
                            // Verificar que realmente es una instancia de SimpleItem
                            if (!$simpleItem instanceof \App\Models\SimpleItem) {
                                throw new \Exception('Error: El item relacionado no es un SimpleItem válido');
                            }
                            
                            // Filtrar solo los campos que pertenecen al SimpleItem
                            $simpleItemData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            // Actualizar el SimpleItem
                            $simpleItem->fill($simpleItemData);
                            
                            // Recalcular automáticamente
                            if (method_exists($simpleItem, 'calculateAll')) {
                                $simpleItem->calculateAll();
                            }
                            $simpleItem->save();
                            
                            // Actualizar también el DocumentItem con los nuevos valores
                            $record->update([
                                'description' => 'SimpleItem: ' . $simpleItem->description,
                                'quantity' => $simpleItem->quantity,
                                'unit_price' => $simpleItem->final_price / $simpleItem->quantity,
                                'total_price' => $simpleItem->final_price
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\MagazineItem' && $record->itemable) {
                            // Manejar edición de MagazineItems
                            $record->load('itemable');
                            $magazine = $record->itemable;
                            
                            // Verificar que es una instancia válida
                            if (!$magazine instanceof \App\Models\MagazineItem) {
                                throw new \Exception('Error: El item relacionado no es un MagazineItem válido');
                            }
                            
                            // Filtrar campos del MagazineItem
                            $magazineData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            // Actualizar el MagazineItem
                            $magazine->fill($magazineData);
                            
                            // Recalcular automáticamente
                            if (method_exists($magazine, 'calculateAll')) {
                                $magazine->calculateAll();
                            }
                            $magazine->save();
                            
                            // Actualizar también el DocumentItem con los nuevos valores
                            $unitPrice = $magazine->quantity > 0 ? $magazine->final_price / $magazine->quantity : 0;
                            $record->update([
                                'description' => 'Revista: ' . $magazine->description,
                                'quantity' => $magazine->quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $magazine->final_price
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\CustomItem' && $record->itemable) {
                            // Manejar edición de CustomItems
                            $record->load('itemable');
                            $customItem = $record->itemable;
                            
                            // Verificar que es una instancia válida
                            if (!$customItem instanceof \App\Models\CustomItem) {
                                throw new \Exception('Error: El item relacionado no es un CustomItem válido');
                            }
                            
                            // Actualizar el CustomItem (el total se calcula automáticamente en el modelo)
                            $customItem->fill([
                                'description' => $data['description'],
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'notes' => $data['notes'] ?? null,
                            ]);
                            $customItem->save();
                            
                            // Actualizar también el DocumentItem con los nuevos valores
                            $record->update([
                                'description' => 'Personalizado: ' . $customItem->description,
                                'quantity' => $customItem->quantity,
                                'unit_price' => $customItem->unit_price,
                                'total_price' => $customItem->total_price
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable) {
                            // Manejar edición de TalonarioItems con acabados
                            $record->load('itemable');
                            $talonario = $record->itemable;
                            
                            // Verificar que es una instancia válida
                            if (!$talonario instanceof \App\Models\TalonarioItem) {
                                throw new \Exception('Error: El item relacionado no es un TalonarioItem válido');
                            }
                            
                            // Filtrar campos del TalonarioItem
                            $talonarioData = array_filter($data, function($key) {
                                return !in_array($key, ['item_type', 'itemable_type', 'itemable_id', 'finishing_ids']);
                            }, ARRAY_FILTER_USE_KEY);
                            
                            // Asegurar valores por defecto para campos requeridos
                            $talonarioData['prefijo'] = $talonarioData['prefijo'] ?? 'Nº';
                            $talonarioData['design_value'] = $talonarioData['design_value'] ?? 0;
                            $talonarioData['transport_value'] = $talonarioData['transport_value'] ?? 0;
                            
                            // Actualizar el TalonarioItem
                            $talonario->fill($talonarioData);
                            $talonario->save();
                            
                            // Procesar acabados si se proporcionaron
                            if (isset($data['finishing_ids']) && is_array($data['finishing_ids'])) {
                                // Limpiar acabados existentes
                                $talonario->finishings()->detach();
                                
                                // Agregar nuevos acabados
                                foreach ($data['finishing_ids'] as $finishingId) {
                                    $finishing = \App\Models\Finishing::find($finishingId);
                                    if ($finishing) {
                                        // Calcular cantidad y costo según el tipo de acabado
                                        if ($finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::POR_NUMERO) {
                                            // Por número: usar total de números
                                            $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                                            $quantity = $totalNumbers * $talonario->quantity;
                                        } else {
                                            // Por talonario: usar cantidad de talonarios
                                            $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                                            $totalTalonarios = ceil($totalNumbers / $talonario->numeros_por_talonario);
                                            $quantity = $totalTalonarios * $talonario->quantity;
                                        }
                                        
                                        $totalCost = $quantity * $finishing->unit_price;
                                        
                                        $talonario->finishings()->attach($finishingId, [
                                            'quantity' => $quantity,
                                            'unit_cost' => $finishing->unit_price,
                                            'total_cost' => $totalCost,
                                            'finishing_options' => null,
                                            'notes' => null,
                                        ]);
                                    }
                                }
                            }
                            
                            // Recalcular precios del talonario
                            $talonario->calculateAll();
                            $talonario->save();
                            
                            // Actualizar también el DocumentItem con los nuevos valores
                            $unitPrice = $talonario->quantity > 0 ? $talonario->final_price / $talonario->quantity : 0;
                            $record->update([
                                'description' => 'Talonario: ' . $talonario->description,
                                'quantity' => $talonario->quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $talonario->final_price
                            ]);
                        } elseif ($record->itemable_type === 'App\\Models\\DigitalItem' && $record->itemable) {
                            // Manejar edición de DigitalItems con acabados
                            $digitalItem = $record->itemable;
                            
                            // Procesar acabados
                            $finishingsData = $data['finishings'] ?? [];
                            $finishingsCost = 0;
                            
                            // Limpiar acabados existentes
                            $digitalItem->finishings()->detach();
                            
                            // Agregar nuevos acabados
                            if (!empty($finishingsData)) {
                                foreach ($finishingsData as $finishingData) {
                                    if (isset($finishingData['finishing_id']) && isset($finishingData['calculated_cost'])) {
                                        $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
                                        
                                        if ($finishing) {
                                            $finishingParams = [
                                                'quantity' => $finishingData['quantity'] ?? 1,
                                                'width' => $finishingData['width'] ?? null,
                                                'height' => $finishingData['height'] ?? null,
                                            ];
                                            
                                            $digitalItem->addFinishing($finishing, $finishingParams);
                                            $finishingsCost += (float) $finishingData['calculated_cost'];
                                        }
                                    }
                                }
                            }
                            
                            // Recalcular precio total del item
                            $basePrice = $digitalItem->calculateTotalPrice(['quantity' => $data['quantity']]);
                            $totalPrice = $basePrice + $finishingsCost;
                            $unitPrice = $totalPrice / $data['quantity'];
                            
                            // Actualizar DocumentItem
                            $record->update([
                                'quantity' => $data['quantity'],
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                            ]);
                        } else {
                            // Para otros tipos de items, actualizar los datos básicos
                            $totalPrice = $data['quantity'] * $data['unit_price'];
                            
                            // Actualizar el item relacionado si existe
                            if ($record->itemable) {
                                $record->itemable->update([
                                    'description' => $data['description'],
                                    'quantity' => $data['quantity'],
                                ]);
                            }
                            
                            // Actualizar el DocumentItem
                            $record->update([
                                'description' => $data['description'],
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'total_price' => $totalPrice
                            ]);
                        }
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                        
                        return $data;
                    })
                    ->modalWidth('7xl')
                    ->slideOver()
                    ->successNotificationTitle('Item actualizado correctamente')
                    ->after(function () {
                        // Refrescar la tabla después de editar
                        $this->dispatch('$refresh');
                    }),
                    
                Action::make('view_details')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(function ($record) {
                        if (!$record->itemable) {
                            return new \Illuminate\Support\HtmlString('<p class="text-gray-500">No se encontró información del item</p>');
                        }
                        
                        $item = $record->itemable;
                        $content = '<div class="space-y-6">';
                        
                        // Información básica común para todos los tipos
                        $content .= '<div>';
                        $content .= '<h3 class="text-lg font-semibold mb-3">Información del Item</h3>';
                        $content .= '<div class="grid grid-cols-2 gap-4 text-sm">';
                        $content .= '<div><strong>Tipo:</strong> ' . class_basename($record->itemable_type) . '</div>';
                        $content .= '<div><strong>Descripción:</strong> ' . ($item->description ?? 'N/A') . '</div>';
                        
                        // Información específica según el tipo de item
                        if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                            $content .= '<div><strong>Cantidad:</strong> ' . number_format($item->quantity) . ' uds</div>';
                            $content .= '<div><strong>Dimensiones:</strong> ' . $item->horizontal_size . ' × ' . $item->vertical_size . ' cm</div>';
                            $content .= '<div><strong>Tintas:</strong> ' . $item->ink_front_count . 'x' . $item->ink_back_count . '</div>';
                            $content .= '<div><strong>Papel:</strong> ' . ($item->paper->name ?? 'N/A') . '</div>';
                            $content .= '<div><strong>Máquina:</strong> ' . ($item->printingMachine->name ?? 'N/A') . '</div>';
                        } elseif ($record->itemable_type === 'App\\Models\\MagazineItem') {
                            $content .= '<div><strong>Cantidad:</strong> ' . number_format($item->quantity) . ' revistas</div>';
                            $content .= '<div><strong>Dimensiones Cerrada:</strong> ' . $item->closed_width . ' × ' . $item->closed_height . ' cm</div>';
                            $content .= '<div><strong>Encuadernación:</strong> ' . ucfirst($item->binding_type) . ' (' . $item->binding_side . ')</div>';
                            $content .= '<div><strong>Total Páginas:</strong> ' . $item->total_pages . ' págs</div>';
                            $content .= '<div><strong>Tipos de Página:</strong> ' . $item->pages->count() . '</div>';
                        } elseif ($record->itemable_type === 'App\\Models\\TalonarioItem') {
                            $content .= '<div><strong>Cantidad:</strong> ' . number_format($item->quantity) . ' talonarios</div>';
                            $content .= '<div><strong>Dimensiones:</strong> ' . $item->ancho . ' × ' . $item->alto . ' cm</div>';
                            $content .= '<div><strong>Numeración:</strong> ' . $item->numbering_range . '</div>';
                            $content .= '<div><strong>Por Talonario:</strong> ' . $item->numeros_por_talonario . ' números</div>';
                            $content .= '<div><strong>Total Hojas:</strong> ' . $item->sheets->count() . ' tipos</div>';
                            $content .= '<div><strong>Acabados:</strong> ' . $item->finishings->count() . ' seleccionados</div>';
                        } else {
                            // Para otros tipos de items, mostrar campos básicos
                            $content .= '<div><strong>Cantidad:</strong> ' . number_format($record->quantity ?? 0) . ' uds</div>';
                            $content .= '<div><strong>Precio Unitario:</strong> $' . number_format($record->unit_price ?? 0, 2) . '</div>';
                            $content .= '<div><strong>Precio Total:</strong> $' . number_format($record->total_price ?? 0, 2) . '</div>';
                        }
                        
                        $content .= '</div>';
                        $content .= '</div>';
                        
                        // Información específica para SimpleItems
                        if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                            $options = method_exists($item, 'getMountingOptions') ? $item->getMountingOptions() : [];
                            $breakdown = method_exists($item, 'getDetailedCostBreakdown') ? $item->getDetailedCostBreakdown() : [];
                            $validations = method_exists($item, 'validateTechnicalViability') ? $item->validateTechnicalViability() : [];
                        
                            // Opciones de montaje
                        if (!empty($options)) {
                            $content .= '<div>';
                            $content .= '<h3 class="text-lg font-semibold mb-3">Opciones de Montaje</h3>';
                            foreach ($options as $index => $option) {
                                $isSelected = $index === 0 ? ' (SELECCIONADO)' : '';
                                $content .= '<div class="p-3 bg-gray-50 rounded mb-2">';
                                $content .= '<div class="flex justify-between">';
                                $content .= '<div>';
                                $content .= '<strong>' . ucfirst($option->orientation) . $isSelected . '</strong><br>';
                                $content .= '<small class="text-gray-600">';
                                $content .= $option->cutsPerSheet . ' cortes/pliego • ';
                                $content .= $option->sheetsNeeded . ' pliegos • ';
                                $content .= number_format($option->utilizationPercentage, 1) . '% aprovechamiento';
                                $content .= '</small>';
                                $content .= '</div>';
                                $content .= '<div class="text-right">';
                                $content .= '<strong>$' . number_format($option->paperCost, 0) . '</strong><br>';
                                $content .= '<small class="text-gray-500">papel</small>';
                                $content .= '</div>';
                                $content .= '</div>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        }
                        
                        // Desglose de costos
                        if (!empty($breakdown)) {
                            $content .= '<div>';
                            $content .= '<h3 class="text-lg font-semibold mb-3">Desglose de Costos</h3>';
                            $content .= '<div class="space-y-2">';
                            foreach ($breakdown as $key => $detail) {
                                $content .= '<div class="flex justify-between py-2 border-b border-gray-100">';
                                $content .= '<div>';
                                $content .= '<strong>' . $detail['description'] . '</strong><br>';
                                $content .= '<small class="text-gray-600">' . $detail['detail'] . '</small>';
                                $content .= '</div>';
                                $content .= '<span class="font-semibold">' . $detail['cost'] . '</span>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                            
                            // Total
                            $content .= '<div class="mt-4 pt-4 border-t-2 border-gray-200">';
                            $content .= '<div class="flex justify-between text-lg font-bold">';
                            $content .= '<span>PRECIO FINAL</span>';
                            $content .= '<span class="text-blue-600">$' . number_format($item->final_price, 2) . '</span>';
                            $content .= '</div>';
                            $content .= '<div class="text-center text-sm text-gray-600 mt-1">';
                            $content .= 'Precio unitario: $' . number_format($item->final_price / $item->quantity, 4);
                            $content .= '</div>';
                            $content .= '</div>';
                        }
                        
                        // Validaciones
                        if (!empty($validations)) {
                            $content .= '<div>';
                            $content .= '<h3 class="text-lg font-semibold mb-3">Validaciones</h3>';
                            foreach ($validations as $validation) {
                                $color = $validation['type'] === 'error' ? 'red' : 'yellow';
                                $content .= '<div class="p-2 bg-' . $color . '-50 border border-' . $color . '-200 rounded mb-2 text-' . $color . '-800">';
                                $content .= $validation['message'];
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        } else {
                            $content .= '<div class="p-3 bg-green-50 border border-green-200 rounded text-green-800">';
                            $content .= '✅ Todas las validaciones pasaron correctamente';
                            $content .= '</div>';
                        }
                        
                        } // Cerrar el bloque if SimpleItem
                        
                        // Información específica para MagazineItems
                        if ($record->itemable_type === 'App\\Models\\MagazineItem') {
                            $breakdown = method_exists($item, 'getDetailedCostBreakdown') ? $item->getDetailedCostBreakdown() : [];
                            $validations = method_exists($item, 'validateTechnicalViability') ? $item->validateTechnicalViability() : [];
                            
                            // Páginas de la revista
                            $content .= '<div>';
                            $content .= '<h3 class="text-lg font-semibold mb-3">Páginas de la Revista</h3>';
                            
                            if ($item->pages && $item->pages->count() > 0) {
                                foreach ($item->pages as $page) {
                                    $content .= '<div class="p-3 bg-blue-50 rounded mb-2">';
                                    $content .= '<div class="flex justify-between">';
                                    $content .= '<div>';
                                    $content .= '<strong>' . ucfirst($page->page_type) . '</strong> (Orden: ' . $page->page_order . ')<br>';
                                    $content .= '<small class="text-gray-600">';
                                    $content .= 'Cantidad: ' . $page->page_quantity . ' páginas<br>';
                                    if ($page->simpleItem) {
                                        $content .= 'SimpleItem: ' . $page->simpleItem->description;
                                    }
                                    $content .= '</small>';
                                    $content .= '</div>';
                                    $content .= '<div class="text-right">';
                                    $content .= '<strong>$' . number_format($page->total_cost ?? 0, 2) . '</strong><br>';
                                    $content .= '<small class="text-gray-500">total página</small>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                            } else {
                                $content .= '<p class="text-gray-500 italic">No hay páginas agregadas aún</p>';
                            }
                            $content .= '</div>';
                            
                            // Acabados de la revista
                            if ($item->finishings && $item->finishings->count() > 0) {
                                $content .= '<div>';
                                $content .= '<h3 class="text-lg font-semibold mb-3">Acabados</h3>';
                                foreach ($item->finishings as $finishing) {
                                    $content .= '<div class="p-2 bg-purple-50 rounded mb-2">';
                                    $content .= '<div class="flex justify-between">';
                                    $content .= '<span>' . $finishing->name . '</span>';
                                    $content .= '<span class="font-medium">$' . number_format($finishing->pivot->total_cost ?? 0, 2) . '</span>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';
                            }
                            
                            // Desglose de costos
                            if (!empty($breakdown)) {
                                $content .= '<div>';
                                $content .= '<h3 class="text-lg font-semibold mb-3">Desglose de Costos</h3>';
                                $content .= '<div class="space-y-2">';
                                
                                if (isset($breakdown['pages']['total'])) {
                                    $content .= '<div class="flex justify-between p-2 bg-blue-50 rounded">';
                                    $content .= '<span>Páginas</span>';
                                    $content .= '<span class="font-medium">$' . number_format($breakdown['pages']['total'], 2) . '</span>';
                                    $content .= '</div>';
                                }
                                
                                if (isset($breakdown['binding']['total'])) {
                                    $content .= '<div class="flex justify-between p-2 bg-green-50 rounded">';
                                    $content .= '<span>Encuadernación</span>';
                                    $content .= '<span class="font-medium">$' . number_format($breakdown['binding']['total'], 2) . '</span>';
                                    $content .= '</div>';
                                }
                                
                                if (isset($breakdown['assembly']['total'])) {
                                    $content .= '<div class="flex justify-between p-2 bg-yellow-50 rounded">';
                                    $content .= '<span>Armado</span>';
                                    $content .= '<span class="font-medium">$' . number_format($breakdown['assembly']['total'], 2) . '</span>';
                                    $content .= '</div>';
                                }
                                
                                if (isset($breakdown['summary']['final_price'])) {
                                    $content .= '<div class="flex justify-between p-3 bg-gray-100 rounded font-semibold text-lg">';
                                    $content .= '<span>Total Final</span>';
                                    $content .= '<span>$' . number_format($breakdown['summary']['final_price'], 2) . '</span>';
                                    $content .= '</div>';
                                }
                                
                                $content .= '</div>';
                                $content .= '</div>';
                            }
                            
                            // Validaciones técnicas
                            if (!empty($validations)) {
                                $content .= '<div>';
                                $content .= '<h3 class="text-lg font-semibold mb-3">Validaciones Técnicas</h3>';
                                
                                if (isset($validations['errors']) && !empty($validations['errors'])) {
                                    foreach ($validations['errors'] as $error) {
                                        $content .= '<div class="p-3 bg-red-50 border border-red-200 rounded text-red-800 mb-2">';
                                        $content .= '❌ ' . $error;
                                        $content .= '</div>';
                                    }
                                }
                                
                                if (isset($validations['warnings']) && !empty($validations['warnings'])) {
                                    foreach ($validations['warnings'] as $warning) {
                                        $content .= '<div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 mb-2">';
                                        $content .= '⚠️ ' . $warning;
                                        $content .= '</div>';
                                    }
                                }
                                
                                if (isset($validations['isValid']) && $validations['isValid'] && empty($validations['warnings'])) {
                                    $content .= '<div class="p-3 bg-green-50 border border-green-200 rounded text-green-800">';
                                    $content .= '✅ Todas las validaciones pasaron correctamente';
                                    $content .= '</div>';
                                }
                                
                                $content .= '</div>';
                            }
                        } // Cerrar el bloque if MagazineItem
                        
                        $content .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalWidth('4xl'),
                    
                Action::make('duplicate')
                    ->label('')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('secondary')
                    ->visible(fn ($record) => $record->itemable !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar Item')
                    ->modalDescription('¿Deseas crear una copia de este item en el documento?')
                    ->action(function ($record) {
                        if ($record->itemable) {
                            // Duplicar el item relacionado
                            $newItem = $record->itemable->replicate();
                            $newItem->description = $newItem->description . ' (Copia)';
                            $newItem->save();
                            
                            // Crear nuevo DocumentItem
                            if ($record->itemable_type === 'App\\Models\\SimpleItem') {
                                // Para SimpleItems, usar los cálculos automáticos
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => 'SimpleItem: ' . $newItem->description,
                                    'quantity' => $newItem->quantity,
                                    'unit_price' => $newItem->final_price / $newItem->quantity,
                                    'total_price' => $newItem->final_price
                                ]);
                            } elseif ($record->itemable_type === 'App\\Models\\MagazineItem') {
                                // Para MagazineItems, duplicar también las páginas y relaciones
                                $originalMagazine = $record->itemable;
                                
                                // Duplicar las páginas asociadas
                                foreach ($originalMagazine->pages as $page) {
                                    $newItem->pages()->create([
                                        'simple_item_id' => $page->simple_item_id,
                                        'page_type' => $page->page_type,
                                        'page_order' => $page->page_order,
                                        'page_quantity' => $page->page_quantity,
                                        'page_notes' => $page->page_notes,
                                    ]);
                                }
                                
                                // Duplicar acabados
                                foreach ($originalMagazine->finishings as $finishing) {
                                    $newItem->finishings()->attach($finishing->id, [
                                        'quantity' => $finishing->pivot->quantity,
                                        'unit_cost' => $finishing->pivot->unit_cost,
                                        'total_cost' => $finishing->pivot->total_cost,
                                        'finishing_options' => $finishing->pivot->finishing_options,
                                        'notes' => $finishing->pivot->notes,
                                    ]);
                                }
                                
                                // Recalcular precios de la revista duplicada
                                $newItem->calculateAll();
                                $newItem->save();
                                
                                $unitPrice = $newItem->quantity > 0 ? $newItem->final_price / $newItem->quantity : 0;
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => 'Revista: ' . $newItem->description,
                                    'quantity' => $newItem->quantity,
                                    'unit_price' => $unitPrice,
                                    'total_price' => $newItem->final_price
                                ]);
                            } else {
                                // Para otros tipos de items, copiar los datos del DocumentItem original
                                $this->getOwnerRecord()->items()->create([
                                    'itemable_type' => $record->itemable_type,
                                    'itemable_id' => $newItem->id,
                                    'description' => $record->description . ' (Copia)',
                                    'quantity' => $record->quantity,
                                    'unit_price' => $record->unit_price,
                                    'total_price' => $record->total_price
                                ]);
                            }
                            
                            // Recalcular totales
                            $this->getOwnerRecord()->recalculateTotals();
                        }
                    })
                    ->successNotificationTitle('Item duplicado correctamente')
                    ->after(function () {
                        $this->dispatch('$refresh');
                    }),
                    
                DeleteAction::make()
                    ->label('')
                    ->after(function ($record) {
                        // Eliminar el item relacionado también
                        if ($record->itemable) {
                            $record->itemable->delete();
                        }
                        
                        // Recalcular totales del documento
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
                    
                Action::make('add_sheet')
                    ->label('')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->tooltip('Agregar Hoja')
                    ->visible(function ($record) {
                        return $record->itemable_type === 'App\\Models\\TalonarioItem' && $record->itemable !== null;
                    })
                    ->modalHeading('Crear Nueva Hoja para el Talonario')
                    ->modalWidth('7xl')
                    ->form(fn() => [
                        \Filament\Forms\Components\Section::make('Información de la Hoja')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('sheet_type')
                                            ->label('Tipo de Hoja')
                                            ->required()
                                            ->options([
                                                'original' => 'Original',
                                                'copia_1' => '1ª Copia',
                                                'copia_2' => '2ª Copia',
                                                'copia_3' => '3ª Copia'
                                            ])
                                            ->default('original'),
                                            
                                        \Filament\Forms\Components\Select::make('paper_color')
                                            ->label('Color del Papel')
                                            ->required()
                                            ->options([
                                                'blanco' => '🤍 Blanco',
                                                'amarillo' => '💛 Amarillo',
                                                'rosado' => '💗 Rosado',
                                                'azul' => '💙 Azul',
                                                'verde' => '💚 Verde',
                                                'naranja' => '🧡 Naranja'
                                            ])
                                            ->default('blanco'),
                                            
                                        \Filament\Forms\Components\TextInput::make('sheet_order')
                                            ->label('Orden')
                                            ->numeric()
                                            ->required()
                                            ->default(function ($livewire) {
                                                $record = $livewire->ownerRecord ?? $livewire->record;
                                                if ($record && $record->itemable) {
                                                    return $record->itemable->getNextSheetOrder();
                                                }
                                                return 1;
                                            })
                                            ->minValue(1)
                                            ->helperText('Orden de la hoja en el talonario'),
                                    ]),
                                    
                                \Filament\Forms\Components\Textarea::make('description')
                                    ->label('Descripción del Contenido')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Describe el contenido de esta hoja...'),
                            ]),
                            
                        \Filament\Forms\Components\Section::make('Materiales')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(2)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('paper_id')
                                            ->label('Papel')
                                            ->options(function () {
                                                $companyId = auth()->user()->company_id ?? 1;
                                                return \App\Models\Paper::query()
                                                    ->where('company_id', $companyId)
                                                    ->get()
                                                    ->mapWithKeys(function ($paper) {
                                                        $label = $paper->full_name ?: ($paper->code . ' - ' . $paper->name);
                                                        return [$paper->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Seleccionar papel'),
                                            
                                        \Filament\Forms\Components\Select::make('printing_machine_id')
                                            ->label('Máquina de Impresión')
                                            ->options(function () {
                                                $companyId = auth()->user()->company_id ?? 1;
                                                return \App\Models\PrintingMachine::query()
                                                    ->where('company_id', $companyId)
                                                    ->get()
                                                    ->mapWithKeys(function ($machine) {
                                                        $label = $machine->name . ' - ' . ucfirst($machine->type);
                                                        return [$machine->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Seleccionar máquina'),
                                    ]),
                            ]),
                            
                        \Filament\Forms\Components\Section::make('Configuración de Tintas')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Forms\Components\TextInput::make('ink_front_count')
                                            ->label('Tintas Frente')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(0)
                                            ->maxValue(8)
                                            ->helperText('Talonarios normalmente usan 1 tinta (negro)'),
                                            
                                        \Filament\Forms\Components\TextInput::make('ink_back_count')
                                            ->label('Tintas Reverso')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(8),
                                            
                                        \Filament\Forms\Components\Select::make('front_back_plate')
                                            ->label('Placa Frente y Reverso')
                                            ->options([
                                                0 => 'No - Placas separadas',
                                                1 => 'Sí - Misma placa'
                                            ])
                                            ->default(0)
                                            ->required()
                                            ->helperText('Para talonarios normalmente es "No"'),
                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, $record, $livewire) {
                        $talonario = $record->itemable;
                        
                        // Extraer datos específicos de la hoja
                        $sheetType = $data['sheet_type'] ?? 'original';
                        $paperColor = $data['paper_color'] ?? 'blanco';
                        $sheetOrder = $data['sheet_order'] ?? 1;
                        
                        // Calcular cantidad correcta basada en el talonario
                        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
                        $correctQuantity = $totalNumbers * $talonario->quantity;
                        
                        // Preparar datos del SimpleItem (sin campos de hoja)
                        $simpleItemData = $data;
                        unset($simpleItemData['sheet_type'], $simpleItemData['paper_color'], $simpleItemData['sheet_order']);
                        
                        // Configurar dimensiones y cantidad automáticamente
                        $simpleItemData['quantity'] = $correctQuantity;
                        $simpleItemData['horizontal_size'] = $talonario->ancho;
                        $simpleItemData['vertical_size'] = $talonario->alto;
                        $simpleItemData['profit_percentage'] = 0; // Sin ganancia doble
                        
                        // Asegurar que front_back_plate sea boolean
                        $simpleItemData['front_back_plate'] = (bool)($simpleItemData['front_back_plate'] ?? false);
                        
                        // Crear el SimpleItem
                        $simpleItem = \App\Models\SimpleItem::create(array_merge($simpleItemData, [
                            'company_id' => auth()->user()->company_id,
                            'user_id' => auth()->id(),
                            'description' => $data['description'],
                        ]));

                        // Crear la hoja del talonario
                        \App\Models\TalonarioSheet::create([
                            'talonario_item_id' => $talonario->id,
                            'simple_item_id' => $simpleItem->id,
                            'sheet_type' => $sheetType,
                            'sheet_order' => $sheetOrder,
                            'paper_color' => $paperColor,
                            'sheet_notes' => $data['description'],
                        ]);

                        // Recalcular precios del talonario
                        $talonario->calculateAll();
                        $talonario->save();
                        
                        // Recalcular el DocumentItem
                        $record->calculateAndUpdatePrices();

                        // Notificación de éxito
                        \Filament\Notifications\Notification::make()
                            ->title('Hoja agregada correctamente')
                            ->body("La hoja '{$sheetType}' ({$paperColor}) se ha creado y agregado al talonario.")
                            ->success()
                            ->send();
                            
                        // Refrescar la tabla
                        $livewire->dispatch('$refresh');
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Eliminar los items relacionados también
                            foreach ($records as $record) {
                                if ($record->itemable) {
                                    $record->itemable->delete();
                                }
                            }
                            
                            // Recalcular totales del documento
                            $this->getOwnerRecord()->recalculateTotals();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}