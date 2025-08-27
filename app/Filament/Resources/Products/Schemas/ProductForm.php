<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use App\Models\Contact;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información Básica del Producto')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre del Producto')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Tarjetas de presentación premium')
                                    ->columnSpanFull(),
                                    
                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Descripción detallada del producto...'),
                                    
                                TextInput::make('code')
                                    ->label('Código del Producto')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Se generará automáticamente si se deja vacío')
                                    ->helperText('Código único para identificar el producto'),
                            ]),
                    ]),
                    
                Section::make('Información de Precios')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('purchase_price')
                                    ->label('Precio de Compra')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->placeholder('0.00'),
                                    
                                TextInput::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->placeholder('0.00'),
                                    
                                Placeholder::make('profit_margin')
                                    ->label('Margen de Ganancia')
                                    ->content(function ($get) {
                                        $purchase = $get('purchase_price') ?? 0;
                                        $sale = $get('sale_price') ?? 0;
                                        
                                        if ($purchase == 0 || $sale == 0) {
                                            return '-';
                                        }
                                        
                                        $margin = (($sale - $purchase) / $purchase) * 100;
                                        $profit = $sale - $purchase;
                                        
                                        return number_format($margin, 2) . '% ($' . number_format($profit, 2) . ')';
                                    }),
                            ]),
                    ]),
                    
                Section::make('Información del Proveedor')
                    ->schema([
                        Toggle::make('is_own_product')
                            ->label('¿Es producto propio?')
                            ->default(true)
                            ->live()
                            ->helperText('Activa esta opción si el producto es fabricado o desarrollado por tu empresa'),
                            
                        Select::make('supplier_contact_id')
                            ->label('Proveedor')
                            ->relationship('supplier', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => 
                                $record->name . ' (' . $record->email . ')'
                            )
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => !$get('is_own_product'))
                            ->required(fn ($get) => !$get('is_own_product'))
                            ->helperText('Selecciona el proveedor de este producto'),
                    ]),
                    
                Section::make('Control de Inventario')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Stock Actual')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('unidades')
                                    ->live()
                                    ->placeholder('0'),
                                    
                                TextInput::make('min_stock')
                                    ->label('Stock Mínimo')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('unidades')
                                    ->live()
                                    ->placeholder('0')
                                    ->helperText('Nivel mínimo para alertas'),
                                    
                                Placeholder::make('stock_status')
                                    ->label('Estado del Stock')
                                    ->content(function ($get) {
                                        $stock = $get('stock') ?? 0;
                                        $minStock = $get('min_stock') ?? 0;
                                        
                                        if ($stock == 0) {
                                            return '🔴 Sin Stock';
                                        } elseif ($stock <= $minStock) {
                                            return '🟡 Stock Bajo';
                                        } else {
                                            return '🟢 Stock Normal';
                                        }
                                    }),
                            ]),
                    ]),
                    
                Section::make('Estado del Producto')
                    ->schema([
                        Toggle::make('active')
                            ->label('Producto Activo')
                            ->default(true)
                            ->helperText('Solo los productos activos aparecerán en las cotizaciones'),
                    ]),
                    
                Section::make('Resumen Financiero')
                    ->description('Vista previa de los cálculos para diferentes cantidades')
                    ->schema([
                        Placeholder::make('pricing_examples')
                            ->label('Ejemplos de Precio')
                            ->content(function ($get) {
                                $salePrice = $get('sale_price') ?? 0;
                                
                                if ($salePrice == 0) {
                                    return 'Ingresa un precio de venta para ver los ejemplos';
                                }
                                
                                $quantities = [1, 10, 50, 100, 500, 1000];
                                $content = '<div class="grid grid-cols-3 gap-4 text-sm">';
                                
                                foreach ($quantities as $qty) {
                                    $total = $salePrice * $qty;
                                    $content .= '<div class="p-2 bg-gray-50 rounded">';
                                    $content .= '<div class="font-semibold">' . number_format($qty) . ' unidades</div>';
                                    $content .= '<div class="text-blue-600">$' . number_format($total, 2) . '</div>';
                                    $content .= '</div>';
                                }
                                
                                $content .= '</div>';
                                
                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}