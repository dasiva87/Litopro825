<?php

namespace App\Filament\Resources\DigitalItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Models\DigitalItem;
use App\Models\Contact;

class DigitalItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información Básica del Item Digital')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Código del Item Digital')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Se generará automáticamente si se deja vacío')
                                    ->helperText('Código único para identificar el item digital'),
                                    
                                Select::make('pricing_type')
                                    ->label('Método de Valoración')
                                    ->required()
                                    ->options([
                                        'unit' => 'Por Unidad',
                                        'size' => 'Por Tamaño (m²)'
                                    ])
                                    ->default('unit')
                                    ->live()
                                    ->helperText('Seleccione cómo se calculará el precio'),
                                    
                                Textarea::make('description')
                                    ->label('Descripción del Servicio')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Descripción detallada del servicio digital...'),
                            ]),
                    ]),
                    
                Section::make('Configuración de Precios')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('unit_value')
                                    ->label(fn ($get) => $get('pricing_type') === 'size' ? 'Valor por m²' : 'Valor por Unidad')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->placeholder('0.00')
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        if (!$get('sale_price')) {
                                            $set('sale_price', $state);
                                        }
                                    }),
                                    
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
                                    ->label('Precio de Venta Base')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->placeholder('0.00'),
                            ]),
                            
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
                    
                Section::make('Información del Proveedor')
                    ->schema([
                        Toggle::make('is_own_product')
                            ->label('¿Es servicio propio?')
                            ->default(true)
                            ->live()
                            ->helperText('Activa esta opción si el servicio es desarrollado por tu empresa'),
                            
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
                            ->helperText('Selecciona el proveedor de este servicio'),
                    ]),
                    
                Section::make('Estado del Item')
                    ->schema([
                        Toggle::make('active')
                            ->label('Item Digital Activo')
                            ->default(true)
                            ->helperText('Solo los items activos aparecerán en las cotizaciones'),
                    ]),
                    
                Section::make('Ejemplos de Cálculo')
                    ->description('Vista previa de los cálculos para diferentes parámetros')
                    ->schema([
                        Placeholder::make('pricing_examples')
                            ->label('Ejemplos de Precio')
                            ->content(function ($get) {
                                $unitValue = $get('unit_value') ?? 0;
                                $pricingType = $get('pricing_type') ?? 'unit';
                                
                                if ($unitValue == 0) {
                                    return 'Ingresa un valor unitario para ver los ejemplos';
                                }
                                
                                $content = '<div class="grid grid-cols-2 gap-4 text-sm">';
                                
                                if ($pricingType === 'unit') {
                                    $quantities = [1, 5, 10, 50, 100, 500];
                                    foreach ($quantities as $qty) {
                                        $total = $unitValue * $qty;
                                        $content .= '<div class="p-2 bg-blue-50 rounded">';
                                        $content .= '<div class="font-semibold">' . number_format($qty) . ' unidades</div>';
                                        $content .= '<div class="text-blue-600">$' . number_format($total, 2) . '</div>';
                                        $content .= '</div>';
                                    }
                                } else {
                                    $sizes = [
                                        ['w' => 1, 'h' => 1, 'desc' => '1m × 1m'],
                                        ['w' => 2, 'h' => 1, 'desc' => '2m × 1m'],
                                        ['w' => 1, 'h' => 1.5, 'desc' => '1m × 1.5m'],
                                        ['w' => 3, 'h' => 2, 'desc' => '3m × 2m'],
                                        ['w' => 2, 'h' => 2, 'desc' => '2m × 2m'],
                                        ['w' => 4, 'h' => 3, 'desc' => '4m × 3m'],
                                    ];
                                    
                                    foreach ($sizes as $size) {
                                        $area = $size['w'] * $size['h'];
                                        $total = $area * $unitValue;
                                        $content .= '<div class="p-2 bg-green-50 rounded">';
                                        $content .= '<div class="font-semibold">' . $size['desc'] . ' (' . $area . 'm²)</div>';
                                        $content .= '<div class="text-green-600">$' . number_format($total, 2) . '</div>';
                                        $content .= '</div>';
                                    }
                                }
                                
                                $content .= '</div>';
                                
                                return $content;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
    
    // Mantener compatibilidad con llamadas antiguas
    public static function schema(Schema $schema): Schema
    {
        return self::configure($schema);
    }

    public static function getFormComponents(): array
    {
        return self::configure(new Schema())->getComponents();
    }
}