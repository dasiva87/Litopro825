<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Enums\FinishingMeasurementUnit;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\SimpleItem;
use App\Models\TalonarioItem;
use App\Models\TalonarioSheet;
use Filament\Actions\Action;
use Filament\Forms\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Placeholder;
use Filament\Forms\Textarea;
use Filament\Forms\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Actions;
use Filament\Schemas\Grid;
use Filament\Schemas\Section;
use Filament\Schemas\Components\Wizard\Step;

class TalonarioItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        return [
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
                                ->suffix('talonarios'),

                            TextInput::make('profit_percentage')
                                ->label('Margen de Ganancia')
                                ->numeric()
                                ->required()
                                ->default(25)
                                ->minValue(0)
                                ->maxValue(500)
                                ->suffix('%'),
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
                                ->minValue(1),

                            TextInput::make('numero_final')
                                ->label('Número Final')
                                ->numeric()
                                ->required()
                                ->default(1000)
                                ->minValue(2),
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
                                ->helperText('Cantidad de números en cada talonario'),

                            Placeholder::make('numbering_preview')
                                ->label('Vista Previa')
                                ->content(function ($get, $record) {
                                    if (! $record || ! $record->itemable) {
                                        return '📋 Guardando...';
                                    }

                                    $prefijo = $get('prefijo') ?? $record->itemable->prefijo ?? 'Nº';
                                    $inicial = $get('numero_inicial') ?? $record->itemable->numero_inicial ?? 1;
                                    $final = $get('numero_final') ?? $record->itemable->numero_final ?? 1000;

                                    if ($final <= $inicial) {
                                        return '⚠️ El número final debe ser mayor al inicial';
                                    }

                                    $totalNumbers = ($final - $inicial) + 1;
                                    $numerosporTalonario = $get('numeros_por_talonario') ?? $record->itemable->numeros_por_talonario ?? 25;
                                    $totalTalonarios = ceil($totalNumbers / $numerosporTalonario);

                                    return "📊 Rango: Del {$prefijo}{$inicial} al {$prefijo}{$final}<br>".
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
                                ->helperText('Ancho del talonario completo'),

                            TextInput::make('alto')
                                ->label('Alto')
                                ->numeric()
                                ->required()
                                ->suffix('cm')
                                ->minValue(0)
                                ->step(0.1)
                                ->helperText('Alto del talonario completo'),
                        ]),

                    Placeholder::make('dimensions_preview')
                        ->label('Área Total')
                        ->content(function ($get, $record) {
                            if (! $record || ! $record->itemable) {
                                return '📐 Guardando...';
                            }

                            $ancho = $get('ancho') ?? $record->itemable->ancho ?? 0;
                            $alto = $get('alto') ?? $record->itemable->alto ?? 0;

                            if ($ancho > 0 && $alto > 0) {
                                $area = $ancho * $alto;

                                return "📏 Área: {$ancho} × {$alto} cm = ".number_format($area, 2).' cm²';
                            }

                            return '📐 Ingresa las dimensiones para ver el área';
                        }),
                ]),

            Section::make('Hojas del Talonario')
                ->description('Gestiona las diferentes hojas que componen el talonario')
                ->schema([
                    Placeholder::make('existing_sheets_table')
                        ->label('Hojas Actuales')
                        ->content(function ($record) {
                            if (! $record || ! $record->itemable) {
                                return '📋 Primero guarda el talonario para agregar hojas';
                            }

                            $talonario = $record->itemable;
                            $sheets = $talonario->getSheetsTableData();

                            if (empty($sheets)) {
                                return '📄 No hay hojas agregadas. Usa el botón "Agregar Hoja" para empezar.';
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
                                $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">'.$sheet['order'].'</td>';
                                $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">';
                                $content .= '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">'.$sheet['type'].'</span>';
                                $content .= '</td>';
                                $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">';
                                $content .= '<span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">'.ucfirst($sheet['color']).'</span>';
                                $content .= '</td>';
                                $content .= '<td class="px-3 py-2 text-sm text-gray-900">'.substr($sheet['description'], 0, 50).(strlen($sheet['description']) > 50 ? '...' : '').'</td>';
                                $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">$'.$sheet['unit_price'].'</td>';
                                $content .= '<td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">$'.$sheet['total_cost'].'</td>';
                                $content .= '</tr>';
                            }

                            $content .= '</tbody>';
                            $content .= '</table>';
                            $content .= '</div>';

                            $totalSheets = count($sheets);
                            $content .= "<div class='mt-3 text-sm text-gray-600'>📊 Total: {$totalSheets} hojas configuradas</div>";

                            return $content;
                        })
                        ->html()
                        ->columnSpanFull(),

                    Actions::make([
                        Action::make('add_sheet')
                            ->label('📋 Agregar Hoja')
                            ->icon('heroicon-o-plus-circle')
                            ->color('primary')
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
                                                    ->options(function ($record) {
                                                        $allOptions = [
                                                            'original' => 'Original',
                                                            'copia_1' => '1ª Copia',
                                                            'copia_2' => '2ª Copia',
                                                            'copia_3' => '3ª Copia',
                                                        ];

                                                        if (! $record || ! $record->itemable) {
                                                            return $allOptions;
                                                        }

                                                        // Obtener tipos ya existentes
                                                        $existingTypes = TalonarioSheet::where('talonario_item_id', $record->itemable->id)
                                                            ->pluck('sheet_type')
                                                            ->toArray();

                                                        // Filtrar opciones disponibles
                                                        $availableOptions = [];
                                                        foreach ($allOptions as $key => $label) {
                                                            if (! in_array($key, $existingTypes)) {
                                                                $availableOptions[$key] = $label;
                                                            } else {
                                                                $availableOptions[$key] = $label.' (Ya existe)';
                                                            }
                                                        }

                                                        return $availableOptions;
                                                    })
                                                    ->default(function ($record) {
                                                        if (! $record || ! $record->itemable) {
                                                            return 'original';
                                                        }

                                                        // Buscar el primer tipo disponible
                                                        $existingTypes = TalonarioSheet::where('talonario_item_id', $record->itemable->id)
                                                            ->pluck('sheet_type')
                                                            ->toArray();

                                                        $typeOrder = ['original', 'copia_1', 'copia_2', 'copia_3'];
                                                        foreach ($typeOrder as $type) {
                                                            if (! in_array($type, $existingTypes)) {
                                                                return $type;
                                                            }
                                                        }

                                                        return 'original';
                                                    }),

                                                Select::make('paper_color')
                                                    ->label('Color del Papel')
                                                    ->required()
                                                    ->options([
                                                        'blanco' => '🤍 Blanco',
                                                        'amarillo' => '💛 Amarillo',
                                                        'rosado' => '💗 Rosado',
                                                        'azul' => '💙 Azul',
                                                        'verde' => '💚 Verde',
                                                        'naranja' => '🧡 Naranja',
                                                    ])
                                                    ->default('blanco'),

                                                TextInput::make('sheet_order')
                                                    ->label('Orden')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(function ($record) {
                                                        if (! $record || ! $record->itemable) {
                                                            return 1;
                                                        }

                                                        return $record->itemable->sheets()->count() + 1;
                                                    })
                                                    ->minValue(1)
                                                    ->helperText('Orden de la hoja en el talonario'),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Descripción del Contenido')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Describe el contenido de esta hoja del talonario...'),
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
                                                            ->where('is_active', true)
                                                            ->get()
                                                            ->mapWithKeys(function ($paper) {
                                                                $label = $paper->full_name ?: ($paper->code.' - '.$paper->name);

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
                                                            ->where('is_active', true)
                                                            ->get()
                                                            ->mapWithKeys(function ($machine) {
                                                                $label = $machine->name.' - '.ucfirst($machine->type);

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
                                                        1 => 'Sí - Misma placa',
                                                    ])
                                                    ->default(0)
                                                    ->required()
                                                    ->helperText('Para talonarios normalmente es "No"'),
                                            ]),
                                    ]),

                                Section::make('Costos Adicionales')
                                    ->schema([
                                        Grid::make(3)
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

                                                TextInput::make('rifle_value')
                                                    ->label('Valor Rifle/Placas')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->prefix('$')
                                                    ->placeholder('0'),
                                            ]),
                                    ]),
                            ])
                            ->action(function (array $data, $record) {
                                if (! $record || ! $record->itemable) {
                                    throw new \Exception('No se puede agregar hoja: Talonario no encontrado');
                                }

                                $talonario = $record->itemable;

                                // Extraer datos específicos de la hoja
                                $sheetType = $data['sheet_type'] ?? 'original';
                                $paperColor = $data['paper_color'] ?? 'blanco';
                                $sheetOrder = $data['sheet_order'] ?? 1;

                                // Verificar si ya existe una hoja del mismo tipo
                                $existingSheet = TalonarioSheet::where('talonario_item_id', $talonario->id)
                                    ->where('sheet_type', $sheetType)
                                    ->first();

                                if ($existingSheet) {
                                    Notification::make()
                                        ->title('Hoja duplicada detectada')
                                        ->body("Ya existe una hoja de tipo '{$sheetType}' en este talonario. Cada tipo de hoja debe ser único.")
                                        ->warning()
                                        ->send();

                                    return;
                                }

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
                                $simpleItemData['profit_percentage'] = 0; // Sin ganancia doble, solo en el talonario final

                                // Asegurar que front_back_plate sea boolean
                                $simpleItemData['front_back_plate'] = (bool) ($simpleItemData['front_back_plate'] ?? false);

                                // Crear el SimpleItem
                                $simpleItem = SimpleItem::create(array_merge($simpleItemData, [
                                    'company_id' => auth()->user()->company_id,
                                    'user_id' => auth()->id(),
                                    'description' => $data['description'],
                                ]));

                                // Crear la hoja del talonario
                                TalonarioSheet::create([
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

                                // Recalcular y actualizar el DocumentItem
                                $record->calculateAndUpdatePrices();

                                // Notificación de éxito
                                Notification::make()
                                    ->title('Hoja agregada correctamente')
                                    ->body("La hoja '{$sheetType}' ({$paperColor}) se ha creado y agregado al talonario. Los precios se han recalculado automáticamente.")
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn ($record) => $record && $record->itemable),

                        Action::make('manage_sheets')
                            ->label('Gestionar Hojas')
                            ->icon('heroicon-o-document-plus')
                            ->color('secondary')
                            ->visible(fn ($record) => $record && $record->itemable)
                            ->url(fn ($record) => "/admin/talonario-items/{$record->itemable->id}")
                            ->openUrlInNewTab(),
                    ])
                        ->alignEnd()
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record !== null),
                ]),

            Section::make('Acabados')
                ->schema([
                    CheckboxList::make('selected_finishings')
                        ->label('Acabados Disponibles')
                        ->options(
                            Finishing::query()
                                ->where('active', true)
                                ->whereIn('measurement_unit', [
                                    FinishingMeasurementUnit::POR_NUMERO->value,
                                    FinishingMeasurementUnit::POR_TALONARIO->value,
                                ])
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->descriptions(
                            Finishing::query()
                                ->where('active', true)
                                ->whereIn('measurement_unit', [
                                    FinishingMeasurementUnit::POR_NUMERO->value,
                                    FinishingMeasurementUnit::POR_TALONARIO->value,
                                ])
                                ->pluck('description', 'id')
                                ->toArray()
                        )
                        ->default(function ($record) {
                            return $record && $record->itemable ? $record->itemable->finishings->pluck('id')->toArray() : [];
                        })
                        ->columns(2)
                        ->columnSpanFull()
                        ->helperText('Seleccione los acabados específicos para talonarios'),
                ]),

            Section::make('Costos Adicionales')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('design_value')
                                ->label('Diseño/Arte')
                                ->numeric()
                                ->default(0)
                                ->prefix('$')
                                ->step(0.01)
                                ->minValue(0),

                            TextInput::make('transport_value')
                                ->label('Transporte/Envío')
                                ->numeric()
                                ->default(0)
                                ->prefix('$')
                                ->step(0.01)
                                ->minValue(0),

                            Placeholder::make('cost_summary')
                                ->label('Resumen de Costos')
                                ->content(function ($get, $record) {
                                    if (! $record || ! $record->itemable) {
                                        return '💰 Guarda primero para ver el resumen';
                                    }

                                    $talonario = $record->itemable;
                                    $sheetsTotal = $talonario->sheets_total_cost ?? 0;
                                    $finishingTotal = $talonario->finishing_cost ?? 0;
                                    $design = $get('design_value') ?? $talonario->design_value ?? 0;
                                    $transport = $get('transport_value') ?? $talonario->transport_value ?? 0;

                                    $subtotal = $sheetsTotal + $finishingTotal + $design + $transport;
                                    $profit = ($get('profit_percentage') ?? $talonario->profit_percentage ?? 25) / 100;
                                    $total = $subtotal * (1 + $profit);

                                    return '📊 Hojas: $'.number_format($sheetsTotal, 2).'<br>'.
                                           '🎨 Acabados: $'.number_format($finishingTotal, 2).'<br>'.
                                           '🎨 Diseño: $'.number_format($design, 2).'<br>'.
                                           '🚚 Transporte: $'.number_format($transport, 2).'<br>'.
                                           '<strong>💵 Total: $'.number_format($total, 2).'</strong>';
                                })
                                ->html(),
                        ]),
                ]),

            Section::make('Notas')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Instrucciones especiales, observaciones, etc.'),
                ]),
        ];
    }

    public function fillForm($record): array
    {
        $talonario = $record->itemable;
        if (! $talonario) {
            return [];
        }

        $data = $talonario->toArray();
        $data['selected_finishings'] = $talonario->finishings->pluck('id')->toArray();

        return $data;
    }

    public function handleUpdate($record, array $data): void
    {
        $talonario = $record->itemable;
        if (! $talonario) {
            return;
        }

        // Actualizar datos básicos del talonario
        $talonario->update([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'numero_inicial' => $data['numero_inicial'],
            'numero_final' => $data['numero_final'],
            'numeros_por_talonario' => $data['numeros_por_talonario'],
            'prefijo' => $data['prefijo'],
            'ancho' => $data['ancho'],
            'alto' => $data['alto'],
            'design_value' => $data['design_value'] ?? 0,
            'transport_value' => $data['transport_value'] ?? 0,
            'profit_percentage' => $data['profit_percentage'] ?? 25,
            'notes' => $data['notes'] ?? null,
        ]);

        // Sincronizar acabados
        if (isset($data['selected_finishings'])) {
            $talonario->finishings()->sync($data['selected_finishings']);
        }

        // Recalcular precios
        $talonario->calculateAll();
        $talonario->save();

        // Actualizar DocumentItem
        $record->calculateAndUpdatePrices();
    }

    public function handleCreate($document, array $data): void
    {
        // Crear el TalonarioItem
        $talonario = TalonarioItem::create([
            'company_id' => auth()->user()->company_id,
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'numero_inicial' => $data['numero_inicial'] ?? 1,
            'numero_final' => $data['numero_final'] ?? 1000,
            'numeros_por_talonario' => $data['numeros_por_talonario'] ?? 25,
            'prefijo' => $data['prefijo'] ?? 'Nº',
            'ancho' => $data['ancho'],
            'alto' => $data['alto'],
            'design_value' => $data['design_value'] ?? 0,
            'transport_value' => $data['transport_value'] ?? 0,
            'profit_percentage' => $data['profit_percentage'] ?? 25,
            'notes' => $data['notes'] ?? null,
        ]);

        // Sincronizar acabados si se proporcionaron
        if (isset($data['selected_finishings']) && ! empty($data['selected_finishings'])) {
            $talonario->finishings()->attach($data['selected_finishings']);
        }

        // Crear hojas desde el wizard o usar hojas por defecto
        if (isset($data['sheets']) && ! empty($data['sheets'])) {
            $this->createSheetsFromWizardData($talonario, $data['sheets']);
        } else {
            // Fallback: crear hojas básicas si no hay datos del wizard
            $this->createDefaultSheets($talonario);
        }

        // Calcular precios iniciales
        $talonario->calculateAll();
        $talonario->save();

        // Crear DocumentItem
        $documentItem = $document->items()->create([
            'itemable_type' => TalonarioItem::class,
            'itemable_id' => $talonario->id,
            'description' => 'Talonario: '.$talonario->description,
            'quantity' => $talonario->quantity,
            'unit_price' => $talonario->final_price > 0 ? $talonario->final_price / $talonario->quantity : 0,
            'total_price' => $talonario->final_price,
        ]);

        $sheetsCount = $talonario->sheets()->count();
        Notification::make()
            ->title('Talonario creado correctamente')
            ->body("El talonario ha sido agregado con {$sheetsCount} hojas configuradas. Los precios se han calculado automáticamente.")
            ->success()
            ->send();
    }

    private function createSheetsFromWizardData(TalonarioItem $talonario, array $sheetsData): void
    {
        // Calcular cantidad correcta basada en el talonario
        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
        $correctQuantity = $totalNumbers * $talonario->quantity;

        foreach ($sheetsData as $sheetData) {
            // Validar que no exista ya una hoja del mismo tipo
            $existingSheet = TalonarioSheet::where('talonario_item_id', $talonario->id)
                ->where('sheet_type', $sheetData['sheet_type'])
                ->first();

            if ($existingSheet) {
                continue; // Saltar hojas duplicadas
            }

            // Crear el SimpleItem con datos del wizard
            $simpleItem = SimpleItem::create([
                'company_id' => $talonario->company_id,
                'user_id' => auth()->id(),
                'description' => $sheetData['description'],
                'quantity' => $correctQuantity,
                'horizontal_size' => $talonario->ancho,
                'vertical_size' => $talonario->alto,
                'paper_id' => $sheetData['paper_id'],
                'printing_machine_id' => $sheetData['printing_machine_id'],
                'ink_front_count' => $sheetData['ink_front_count'] ?? 1,
                'ink_back_count' => $sheetData['ink_back_count'] ?? 0,
                'front_back_plate' => (bool) ($sheetData['front_back_plate'] ?? false),
                'profit_percentage' => 0, // Sin ganancia doble, solo en el talonario final
                'design_value' => 0,
                'transport_value' => 0,
                'rifle_value' => 0,
            ]);

            // Crear la hoja del talonario
            TalonarioSheet::create([
                'talonario_item_id' => $talonario->id,
                'simple_item_id' => $simpleItem->id,
                'sheet_type' => $sheetData['sheet_type'],
                'sheet_order' => $sheetData['sheet_order'] ?? 1,
                'paper_color' => $sheetData['paper_color'] ?? 'blanco',
                'sheet_notes' => $sheetData['description'],
            ]);
        }
    }

    private function createDefaultSheets(TalonarioItem $talonario): void
    {
        $defaultSheets = [
            ['sheet_type' => 'original', 'sheet_notes' => 'Hoja original'],
            ['sheet_type' => 'copia_1', 'sheet_notes' => 'Primera copia'],
            ['sheet_type' => 'copia_2', 'sheet_notes' => 'Segunda copia'],
        ];

        foreach ($defaultSheets as $index => $sheetData) {
            // Crear SimpleItem básico para cada hoja
            $simpleItem = SimpleItem::create([
                'company_id' => $talonario->company_id,
                'description' => "{$talonario->description} - {$sheetData['sheet_notes']}",
                'quantity' => $talonario->quantity * $talonario->numeros_por_talonario,
                'horizontal_size' => $talonario->ancho,
                'vertical_size' => $talonario->alto,
                'ink_front_count' => 1,
                'ink_back_count' => 0,
                'profit_percentage' => 25,
                // Valores por defecto básicos
                'design_value' => 0,
                'transport_value' => 0,
                'rifle_value' => 0,
            ]);

            // Crear la hoja del talonario
            TalonarioSheet::create([
                'talonario_item_id' => $talonario->id,
                'simple_item_id' => $simpleItem->id,
                'sheet_type' => $sheetData['sheet_type'],
                'sheet_order' => $index + 1,
                'sheet_notes' => $sheetData['sheet_notes'],
            ]);
        }
    }

    public function getWizardSteps(): array
    {
        return [
            Step::make('Información Básica')
                ->description('Datos generales del talonario')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Textarea::make('description')
                        ->label('Descripción del Talonario')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('Ej: Recibos de caja, Facturas comerciales...'),

                    Grid::make(2)->schema([
                        TextInput::make('quantity')
                            ->label('Cantidad de Talonarios')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1)
                            ->suffix('talonarios'),

                        TextInput::make('profit_percentage')
                            ->label('Margen de Ganancia')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->default(25),
                    ]),
                ]),

            Step::make('Configuración de Numeración')
                ->description('Numeración y dimensiones del talonario')
                ->icon('heroicon-o-hashtag')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('prefijo')
                            ->label('Prefijo')
                            ->default('Nº')
                            ->maxLength(10)
                            ->placeholder('Nº, Rec., Fact.'),

                        TextInput::make('numero_inicial')
                            ->label('Número Inicial')
                            ->numeric()
                            ->required()
                            ->default(1),

                        TextInput::make('numero_final')
                            ->label('Número Final')
                            ->numeric()
                            ->required()
                            ->default(1000),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('numeros_por_talonario')
                            ->label('Números por Talonario')
                            ->numeric()
                            ->required()
                            ->default(25)
                            ->minValue(1),

                        TextInput::make('ancho')
                            ->label('Ancho (cm)')
                            ->numeric()
                            ->required()
                            ->step(0.1)
                            ->suffix('cm'),

                        TextInput::make('alto')
                            ->label('Alto (cm)')
                            ->numeric()
                            ->required()
                            ->step(0.1)
                            ->suffix('cm'),
                    ]),
                ]),

            Step::make('Configuración de Hojas')
                ->description('Define las hojas que tendrá el talonario')
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    Repeater::make('sheets')
                        ->label('Hojas del Talonario')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('sheet_type')
                                    ->label('Tipo de Hoja')
                                    ->required()
                                    ->options([
                                        'original' => 'Original',
                                        'copia_1' => '1ª Copia',
                                        'copia_2' => '2ª Copia',
                                        'copia_3' => '3ª Copia',
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
                                        'naranja' => '🧡 Naranja',
                                    ])
                                    ->default('blanco'),

                                TextInput::make('sheet_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                            ]),

                            Textarea::make('description')
                                ->label('Descripción del Contenido')
                                ->required()
                                ->rows(2)
                                ->columnSpanFull()
                                ->placeholder('Describe el contenido de esta hoja...'),

                            Grid::make(2)->schema([
                                Select::make('paper_id')
                                    ->label('Papel')
                                    ->options(function () {
                                        $companyId = auth()->user()->company_id ?? 1;

                                        return Paper::query()
                                            ->where('company_id', $companyId)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($paper) {
                                                $label = $paper->full_name ?: ($paper->code.' - '.$paper->name);

                                                return [$paper->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable(),

                                Select::make('printing_machine_id')
                                    ->label('Máquina de Impresión')
                                    ->options(function () {
                                        $companyId = auth()->user()->company_id ?? 1;

                                        return PrintingMachine::query()
                                            ->where('company_id', $companyId)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($machine) {
                                                $label = $machine->name.' - '.ucfirst($machine->type);

                                                return [$machine->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('ink_front_count')
                                    ->label('Tintas Frente')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->maxValue(8),

                                TextInput::make('ink_back_count')
                                    ->label('Tintas Reverso')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(8),

                                Select::make('front_back_plate')
                                    ->label('Placa Frente/Reverso')
                                    ->options([
                                        0 => 'Separadas',
                                        1 => 'Misma placa',
                                    ])
                                    ->default(0)
                                    ->required(),
                            ]),
                        ])
                        ->defaultItems(3)
                        ->minItems(1)
                        ->maxItems(4)
                        ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'Hoja sin descripción')
                        ->collapsible()
                        ->columns(1)
                        ->helperText('Configura las hojas que tendrá tu talonario. Cada hoja se calculará como un item sencillo independiente.'),
                ]),
        ];
    }

    // Método de compatibilidad para el wizard antiguo
    public function getWizardStep(): \Filament\Schemas\Components\Wizard\Step
    {
        // Devolver el primer paso para compatibilidad con el sistema existente
        return $this->getWizardSteps()[0];
    }
}
