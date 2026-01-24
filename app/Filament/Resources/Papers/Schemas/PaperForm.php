<?php

namespace App\Filament\Resources\Papers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Contact;

class PaperForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('Ej: BOND75, OPA150, COU300...')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                        return $rule->where('company_id', auth()->user()->company_id);
                                    })
                                    ->alphaDash(),
                                    
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Bond Blanco, Opalina, Couché...'),
                            ]),
                            
                        TextInput::make('weight')
                            ->label('Gramaje')
                            ->numeric()
                            ->suffix('gr/m²')
                            ->minValue(0)
                            ->placeholder('Ej: 75, 90, 150, 300...'),
                    ]),
                    
                Section::make('Dimensiones')
                    ->description('Dimensiones del pliego en centímetros')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('width')
                                    ->label('Ancho')
                                    ->numeric()
                                    ->suffix('cm')
                                    ->required()
                                    ->minValue(0)
                                    ->placeholder('Ej: 70, 100...')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && $get('height')) {
                                            $area = $state * $get('height');
                                            $set('calculated_area', number_format($area, 2) . ' cm²');
                                        }
                                    }),
                                    
                                TextInput::make('height')
                                    ->label('Alto')
                                    ->numeric()
                                    ->suffix('cm')
                                    ->required()
                                    ->minValue(0)
                                    ->placeholder('Ej: 100, 140...')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && $get('width')) {
                                            $area = $state * $get('width');
                                            $set('calculated_area', number_format($area, 2) . ' cm²');
                                        }
                                    }),
                                    
                                Placeholder::make('calculated_area')
                                    ->label('Área Calculada')
                                    ->content(fn ($get) => $get('width') && $get('height') ? 
                                        number_format($get('width') * $get('height'), 2) . ' cm²' : 
                                        '- cm²'
                                    ),
                            ]),
                    ]),
                    
                Section::make('Proveedor')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_own')
                                    ->label('Es Papel Propio')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Desactiva si el papel es de un proveedor externo'),
                                    
                                Select::make('supplier_id')
                                    ->label('Proveedor')
                                    ->options(
                                        Contact::suppliers()
                                            ->active()
                                            ->get()
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(fn ($get) => !$get('is_own'))
                                    ->visible(fn ($get) => !$get('is_own'))
                                    ->helperText('Selecciona el proveedor del papel'),
                            ]),
                    ]),
                    
                Section::make('Información Comercial')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('cost_per_sheet')
                                    ->label('Costo por Pliego')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Costo de adquisición o producción'),
                                    
                                TextInput::make('price')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Precio para cotizaciones'),
                                    
                                TextInput::make('stock')
                                    ->label('Stock Disponible')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('pliegos')
                                    ->helperText('Cantidad en inventario'),
                            ]),
                            
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Solo los papeles activos aparecen en cotizaciones'),

                        Toggle::make('is_public')
                            ->label('Público para clientes')
                            ->default(false)
                            ->helperText('Si está activo, las litografías clientes podrán ver y usar este papel'),
                    ]),
            ]);
    }
}