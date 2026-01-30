<?php

namespace App\Filament\Resources\PrintingMachines\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Contact;

class PrintingMachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre de la Máquina')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Heidelberg Speedmaster, Komori...'),
                                    
                                Select::make('type')
                                    ->label('Tipo de Máquina')
                                    ->options([
                                        'offset' => 'Offset',
                                        'digital' => 'Digital',
                                        'serigrafia' => 'Serigrafía',
                                        'flexografia' => 'Flexografía',
                                        'rotativa' => 'Rotativa',
                                        'plotter' => 'Plotter',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_own')
                                    ->label('Máquina Propia')
                                    ->helperText('Si está desactivado, es máquina de terceros')
                                    ->default(true)
                                    ->live(),
                                    
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
                                    ->helperText('Selecciona el proveedor de la máquina'),
                            ]),
                            
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true)
                            ->helperText('Solo las máquinas activas aparecen en cotizaciones'),

                        Toggle::make('is_public')
                            ->label('Público para clientes')
                            ->default(false)
                            ->helperText('Si está activo, las litografías clientes podrán ver y usar esta máquina'),
                    ]),
                    
                Section::make('Especificaciones Técnicas')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('max_width')
                                    ->label('Ancho Máximo')
                                    ->numeric()
                                    ->suffix('cm')
                                    ->required()
                                    ->minValue(0)
                                    ->placeholder('Ej: 70, 100, 140...')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && $get('max_height')) {
                                            $area = $state * $get('max_height');
                                            $set('calculated_max_area', number_format($area, 2) . ' cm²');
                                        }
                                    }),
                                    
                                TextInput::make('max_height')
                                    ->label('Alto Máximo')
                                    ->numeric()
                                    ->suffix('cm')
                                    ->required()
                                    ->minValue(0)
                                    ->placeholder('Ej: 100, 140, 200...')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && $get('max_width')) {
                                            $area = $state * $get('max_width');
                                            $set('calculated_max_area', number_format($area, 2) . ' cm²');
                                        }
                                    }),
                                    
                                TextInput::make('max_colors')
                                    ->label('Colores Máximos')
                                    ->numeric()
                                    ->required()
                                    ->default(4)
                                    ->minValue(1)
                                    ->maxValue(8)
                                    ->placeholder('Ej: 1, 2, 4, 6, 8...'),
                            ]),
                            
                        Placeholder::make('calculated_max_area')
                            ->label('Área Máxima de Impresión')
                            ->content(fn ($get) => $get('max_width') && $get('max_height') ? 
                                number_format($get('max_width') * $get('max_height'), 2) . ' cm²' : 
                                '- cm²'
                            ),
                    ]),
                    
                Section::make('Información de Costos')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cost_per_impression')
                            ->label('Costo por Millar')
                            ->helperText('Costo por cada 1,000 impresiones')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('Ej: 45000'),

                        TextInput::make('setup_cost')
                            ->label('Costo de Alistamiento')
                            ->helperText('Costo fijo por cada trabajo')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('Ej: 25000'),

                        TextInput::make('costo_ctp')
                            ->label('Costo CTP (Computer-to-Plate)')
                            ->helperText('Costo por cada plancha de impresión')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('Ej: 15000'),
                    ]),
            ]);
    }
}