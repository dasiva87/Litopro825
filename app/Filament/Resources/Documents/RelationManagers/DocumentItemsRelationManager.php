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
use Filament\Schemas\Components\Wizard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items de la Cotización';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Items';

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
                                    Forms\Components\Group::make()
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
                                                ->relationship(
                                                    name: 'itemable',
                                                    titleAttribute: 'description',
                                                    modifyQueryUsing: fn ($query) => $query->where('active', true)->where('company_id', auth()->user()->company_id)
                                                )
                                                ->getOptionLabelFromRecordUsing(function ($record) {
                                                    return $record->code . ' - ' . $record->description . ' (' . $record->pricing_type_name . ')';
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
                                                        ->live(),
                                                        
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
                                                        ->live(),
                                                        
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
                                                        ->live(),
                                                ]),
                                                
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
                                                    
                                                    $calculator = new \App\Services\DigitalItemCalculatorService();
                                                    $params = ['quantity' => $quantity];
                                                    
                                                    if ($item->pricing_type === 'size') {
                                                        $params['width'] = $width;
                                                        $params['height'] = $height;
                                                    }
                                                    
                                                    $errors = $calculator->validateParameters($item, $params);
                                                    
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
                                                ->visible(fn ($get) => filled($get('itemable_id'))),
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
                                                ->relationship(
                                                    name: 'itemable',
                                                    titleAttribute: 'name',
                                                    modifyQueryUsing: fn ($query) => $query->where('active', true)->where('company_id', auth()->user()->company_id)
                                                )
                                                ->getOptionLabelFromRecordUsing(function ($record) {
                                                    $stockStatus = $record->stock == 0 ? ' (SIN STOCK)' : 
                                                                  ($record->isLowStock() ? ' (STOCK BAJO)' : '');
                                                    return $record->name . ' - $' . number_format($record->sale_price, 2) . 
                                                           ' (Stock: ' . $record->stock . ')' . $stockStatus;
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
                        default => 'Otro'
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'App\\Models\\SimpleItem' => 'success',
                        'App\\Models\\Product' => 'purple',
                        'App\\Models\\TalonarioItem' => 'warning',
                        'App\\Models\\MagazineItem' => 'info',
                        'App\\Models\\DigitalItem' => 'primary',
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
                        // Para SimpleItems, usar final_price del item
                        if ($record->itemable && isset($record->itemable->final_price)) {
                            return $record->itemable->final_price;
                        }
                        return $record->total_price ?? 0;
                    })
                    ->money('COP')
                    ->sortable(),
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
                                'total_price' => $simpleItem->final_price
                            ];
                        }
                        
                        // Manejar items digitales
                        elseif ($data['item_type'] === 'digital' && $data['itemable_type'] === 'App\\Models\\DigitalItem') {
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
                            
                            // Calcular precio total
                            $totalPrice = $digitalItem->calculateTotalPrice($params);
                            $unitPrice = $totalPrice / $params['quantity'];
                            
                            // Configurar datos para DocumentItem
                            $data = [
                                'itemable_type' => 'App\\Models\\DigitalItem',
                                'itemable_id' => $digitalItem->id,
                                'description' => 'Digital: ' . $digitalItem->description,
                                'quantity' => $params['quantity'],
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice
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
                                'total_price' => $product->calculateTotalPrice($requestedQuantity)
                            ];
                        }
                        
                        return $data;
                    })
                    ->after(function () {
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