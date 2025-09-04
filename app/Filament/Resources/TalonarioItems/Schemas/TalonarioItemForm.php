<?php

namespace App\Filament\Resources\TalonarioItems\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Models\SimpleItem;
use App\Models\TalonarioSheet;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Enums\FinishingMeasurementUnit;

class TalonarioItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripción del Talonario')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Ej: Recibos de caja, Facturas comerciales, Remisiones de entrega...'),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('quantity')
                                    ->label('Cantidad de Talonarios')
                                    ->numeric()
                                    ->required()
                                    ->default(10)
                                    ->minValue(1)
                                    ->suffix('talonarios')
                                    ->placeholder('10'),
                                    
                                TextInput::make('profit_percentage')
                                    ->label('Margen de Ganancia')
                                    ->numeric()
                                    ->required()
                                    ->default(25)
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->suffix('%')
                                    ->placeholder('25'),
                            ]),
                    ]),
                    
                Section::make('Configuración de Numeración')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('prefijo')
                                    ->label('Prefijo')
                                    ->default('Nº')
                                    ->maxLength(10)
                                    ->placeholder('Nº, Rec., Fact., Rem.')
                                    ->helperText('Prefijo que aparece antes del número'),
                                    
                                TextInput::make('numero_inicial')
                                    ->label('Número Inicial')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->placeholder('1'),
                                    
                                TextInput::make('numero_final')
                                    ->label('Número Final')
                                    ->numeric()
                                    ->required()
                                    ->default(1000)
                                    ->minValue(2)
                                    ->placeholder('1000'),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('numeros_por_talonario')
                                    ->label('Números por Talonario')
                                    ->numeric()
                                    ->required()
                                    ->default(25)
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->placeholder('25')
                                    ->helperText('Cantidad de números en cada talonario'),
                                    
                                Placeholder::make('numbering_preview')
                                    ->label('Vista Previa')
                                    ->content(function ($get, $record) {
                                        $prefijo = $get('prefijo') ?? ($record?->prefijo ?? 'Nº');
                                        $inicial = $get('numero_inicial') ?? ($record?->numero_inicial ?? 1);
                                        $final = $get('numero_final') ?? ($record?->numero_final ?? 1000);
                                        
                                        if ($final <= $inicial) {
                                            return '⚠️ El número final debe ser mayor al inicial';
                                        }
                                        
                                        $totalNumbers = ($final - $inicial) + 1;
                                        $numerosporTalonario = $get('numeros_por_talonario') ?? ($record?->numeros_por_talonario ?? 25);
                                        $totalTalonarios = ceil($totalNumbers / $numerosporTalonario);
                                        
                                        return "📊 Rango: Del {$prefijo}{$inicial} al {$prefijo}{$final}<br>" .
                                               "📈 Total: {$totalNumbers} números • {$totalTalonarios} talonarios";
                                    })
                                    ->html(),
                            ]),
                    ]),
                    
                Section::make('Dimensiones del Talonario')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('ancho')
                                    ->label('Ancho')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('15.0')
                                    ->helperText('Ancho del talonario completo'),
                                    
                                TextInput::make('alto')
                                    ->label('Alto')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->minValue(0)
                                    ->step(0.1)
                                    ->placeholder('10.0')
                                    ->helperText('Alto del talonario completo'),
                            ]),
                            
                        Placeholder::make('dimensions_preview')
                            ->label('Área Total')
                            ->content(function ($get, $record) {
                                $ancho = $get('ancho') ?? ($record?->ancho ?? 0);
                                $alto = $get('alto') ?? ($record?->alto ?? 0);
                                
                                if ($ancho > 0 && $alto > 0) {
                                    $area = $ancho * $alto;
                                    return "📐 Dimensiones: {$ancho}cm × {$alto}cm = {$area}cm²";
                                }
                                
                                return '📐 Ingrese las dimensiones para ver el área';
                            })
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Hojas del Talonario')
                    ->description('Las hojas se crean después de guardar el talonario')
                    ->schema([
                        Placeholder::make('existing_sheets_table')
                            ->label('Hojas Actuales')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '📋 No hay hojas agregadas. Guarde el talonario para comenzar a agregar hojas.';
                                }

                                $sheets = $record->getSheetsTableData();
                                
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
                            
                        Actions::make([
                            Action::make('add_sheet')
                                ->label('📋 Agregar Hoja')
                                ->color('primary')
                                ->icon('heroicon-o-plus-circle')
                                ->modalHeading('Crear Nueva Hoja para el Talonario')
                                ->modalWidth('7xl')
                                ->form([
                                    Section::make('Información de la Hoja')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    Select::make('sheet_type')
                                                        ->label('Tipo de Hoja')
                                                        ->required()
                                                        ->options([
                                                            'original' => 'Original',
                                                            'copia_1' => '1ª Copia',
                                                            'copia_2' => '2ª Copia',
                                                            'copia_3' => '3ª Copia'
                                                        ])
                                                        ->default('original'),
                                                        
                                                    Select::make('paper_color')
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
                                                        
                                                    TextInput::make('sheet_order')
                                                        ->label('Orden')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(1)
                                                        ->helperText('Orden de la hoja en el talonario'),
                                                ]),
                                                
                                            Textarea::make('description')
                                                ->label('Descripción del Contenido')
                                                ->required()
                                                ->rows(3)
                                                ->columnSpanFull()
                                                ->placeholder('Describe el contenido de esta hoja...'),
                                        ]),
                                        
                                    Section::make('Materiales')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('paper_id')
                                                        ->label('Papel')
                                                        ->options(function () {
                                                            $companyId = auth()->user()->company_id ?? 1;
                                                            return Paper::query()
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
                                                        
                                                    Select::make('printing_machine_id')
                                                        ->label('Máquina de Impresión')
                                                        ->options(function () {
                                                            $companyId = auth()->user()->company_id ?? 1;
                                                            return PrintingMachine::query()
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
                                        
                                    Section::make('Configuración de Tintas')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('ink_front_count')
                                                        ->label('Tintas Frente')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(1)
                                                        ->minValue(0)
                                                        ->maxValue(8)
                                                        ->placeholder('1')
                                                        ->helperText('Talonarios normalmente usan 1 tinta (negro)'),
                                                        
                                                    TextInput::make('ink_back_count')
                                                        ->label('Tintas Reverso')
                                                        ->numeric()
                                                        ->required()
                                                        ->default(0)
                                                        ->minValue(0)
                                                        ->maxValue(8)
                                                        ->placeholder('0'),
                                                        
                                                    Select::make('front_back_plate')
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
                                ->action(function (array $data, $record) {
                                    // Extraer datos específicos de la hoja
                                    $sheetType = $data['sheet_type'] ?? 'original';
                                    $paperColor = $data['paper_color'] ?? 'blanco';
                                    $sheetOrder = $data['sheet_order'] ?? 1;
                                    
                                    // Calcular cantidad correcta basada en el talonario
                                    $totalNumbers = ($record->numero_final - $record->numero_inicial) + 1;
                                    $correctQuantity = $totalNumbers * $record->quantity;
                                    
                                    // Preparar datos del SimpleItem (sin campos de hoja)
                                    $simpleItemData = $data;
                                    unset($simpleItemData['sheet_type'], $simpleItemData['paper_color'], $simpleItemData['sheet_order']);
                                    
                                    // Configurar dimensiones y cantidad automáticamente
                                    $simpleItemData['quantity'] = $correctQuantity;
                                    $simpleItemData['horizontal_size'] = $record->ancho;
                                    $simpleItemData['vertical_size'] = $record->alto;
                                    $simpleItemData['profit_percentage'] = 25;
                                    
                                    // Asegurar que front_back_plate sea boolean
                                    $simpleItemData['front_back_plate'] = (bool)($simpleItemData['front_back_plate'] ?? false);
                                    
                                    // Crear el SimpleItem
                                    $simpleItem = SimpleItem::create(array_merge($simpleItemData, [
                                        'company_id' => auth()->user()->company_id,
                                        'user_id' => auth()->id(),
                                        'description' => $data['description'],
                                    ]));

                                    // Crear la hoja del talonario
                                    TalonarioSheet::create([
                                        'talonario_item_id' => $record->id,
                                        'simple_item_id' => $simpleItem->id,
                                        'sheet_type' => $sheetType,
                                        'sheet_order' => $sheetOrder,
                                        'paper_color' => $paperColor,
                                    ]);

                                    // Recalcular precios del talonario
                                    $record->calculateAll();
                                    $record->save();

                                    // Notificación de éxito
                                    Notification::make()
                                        ->title('Hoja agregada correctamente')
                                        ->body("La hoja '{$sheetType}' ({$paperColor}) se ha creado y agregado al talonario.")
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn ($record) => $record !== null),
                        ])
                        ->alignEnd()
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record !== null),
                    ]),
                    
                Section::make('Acabados Específicos')
                    ->schema([
                        CheckboxList::make('finishings')
                            ->label('Acabados para Talonarios')
                            ->relationship('finishings', 'name')
                            ->options(
                                Finishing::query()
                                    ->where('active', true)
                                    ->whereIn('measurement_unit', [
                                        FinishingMeasurementUnit::POR_NUMERO->value,
                                        FinishingMeasurementUnit::POR_TALONARIO->value
                                    ])
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->descriptions(
                                Finishing::query()
                                    ->where('active', true)
                                    ->whereIn('measurement_unit', [
                                        FinishingMeasurementUnit::POR_NUMERO->value,
                                        FinishingMeasurementUnit::POR_TALONARIO->value
                                    ])
                                    ->get()
                                    ->mapWithKeys(function ($finishing) {
                                        $description = $finishing->description;
                                        $price = '$' . number_format($finishing->unit_price, 0);
                                        $unit = $finishing->measurement_unit->label();
                                        return [$finishing->id => "{$description} - {$price} {$unit}"];
                                    })
                                    ->toArray()
                            )
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText('Seleccione los acabados específicos que requiere el talonario'),
                    ]),
                    
                Section::make('Costos Adicionales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('design_value')
                                    ->label('Valor Diseño')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0'),
                                    
                                TextInput::make('transport_value')
                                    ->label('Valor Transporte')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0'),
                            ]),
                    ]),
                    
                Section::make('Resumen de Costos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('sheets_total_cost')
                                    ->label('Costo Total de Hojas')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->sheets_total_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('finishing_cost')
                                    ->label('Costo Acabados')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->finishing_cost ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('total_cost')
                                    ->label('Costo Total')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->total_cost ?? 0, 2);
                                    }),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('final_price')
                                    ->label('Precio Final')
                                    ->content(function ($get, $record) {
                                        if (!$record) return '$0.00';
                                        return '$' . number_format($record->final_price ?? 0, 2);
                                    }),
                                    
                                Placeholder::make('unit_price')
                                    ->label('Precio Unitario')
                                    ->content(function ($get, $record) {
                                        if (!$record || !$record->quantity) return '$0.00';
                                        $unitPrice = $record->final_price / $record->quantity;
                                        return '$' . number_format($unitPrice, 2) . ' / talonario';
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                    
                Section::make('Notas Adicionales')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Notas adicionales sobre el talonario...'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}