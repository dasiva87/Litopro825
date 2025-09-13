<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\TextInput;
use Filament\Forms\Textarea;
use Filament\Forms\Placeholder;
use Filament\Forms\CheckboxList;
use Filament\Schemas\Wizard\Step;
use Filament\Schemas\Section;
use Filament\Schemas\Grid;
use Filament\Schemas\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\TalonarioItem;
use App\Models\TalonarioSheet;
use App\Models\SimpleItem;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Enums\FinishingMeasurementUnit;
use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;

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
                                if (!$record || !$record->itemable) return '📐 Guardando...';
                                
                                $ancho = $get('ancho') ?? $record->itemable->ancho ?? 0;
                                $alto = $get('alto') ?? $record->itemable->alto ?? 0;
                                
                                if ($ancho > 0 && $alto > 0) {
                                    $area = $ancho * $alto;
                                    return "📏 Área: {$ancho} × {$alto} cm = " . number_format($area, 2) . " cm²";
                                }
                                
                                return '📐 Ingresa las dimensiones para ver el área';
                            }),
                    ]),
                    
                Section::make('Hojas del Talonario')
                    ->description('Gestiona las diferentes hojas que componen el talonario')
                    ->schema([
                        Placeholder::make('sheets_info')
                            ->content(function ($record) {
                                if (!$record || !$record->itemable) {
                                    return '📋 Primero guarda el talonario para agregar hojas';
                                }
                                
                                $sheetsCount = $record->itemable->sheets()->count();
                                if ($sheetsCount === 0) {
                                    return '📄 No hay hojas agregadas. Usa el botón "Agregar Hoja" para empezar.';
                                }
                                
                                return "📊 {$sheetsCount} hojas configuradas. Cada hoja se calcula como un item sencillo independiente.";
                            }),
                            
                        Actions::make([
                            Action::make('manage_sheets')
                                ->label('Gestionar Hojas')
                                ->icon('heroicon-o-document-plus')
                                ->color('primary')
                                ->visible(fn ($record) => $record && $record->itemable)
                                ->url(fn ($record) => "/admin/talonario-items/{$record->itemable->id}")
                                ->openUrlInNewTab(),
                        ]),
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
                                        if (!$record || !$record->itemable) {
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
                                        
                                        return "📊 Hojas: $" . number_format($sheetsTotal, 2) . "<br>" .
                                               "🎨 Acabados: $" . number_format($finishingTotal, 2) . "<br>" .
                                               "🎨 Diseño: $" . number_format($design, 2) . "<br>" .
                                               "🚚 Transporte: $" . number_format($transport, 2) . "<br>" .
                                               "<strong>💵 Total: $" . number_format($total, 2) . "</strong>";
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
        if (!$talonario) {
            return [];
        }
        
        $data = $talonario->toArray();
        $data['selected_finishings'] = $talonario->finishings->pluck('id')->toArray();
        
        return $data;
    }
    
    public function handleUpdate($record, array $data): void
    {
        $talonario = $record->itemable;
        if (!$talonario) {
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
        if (isset($data['selected_finishings']) && !empty($data['selected_finishings'])) {
            $talonario->finishings()->attach($data['selected_finishings']);
        }
        
        // Crear hojas básicas (Original, Copia 1, Copia 2)
        $this->createDefaultSheets($talonario);
        
        // Calcular precios iniciales
        $talonario->calculateAll();
        $talonario->save();
        
        // Crear DocumentItem
        $documentItem = $document->items()->create([
            'itemable_type' => TalonarioItem::class,
            'itemable_id' => $talonario->id,
            'description' => 'Talonario: ' . $talonario->description,
            'quantity' => $talonario->quantity,
            'unit_price' => $talonario->final_price > 0 ? $talonario->final_price / $talonario->quantity : 0,
            'total_price' => $talonario->final_price
        ]);
        
        Notification::make()
            ->title('Talonario creado correctamente')
            ->body('El talonario ha sido agregado con hojas básicas. Puedes gestionar las hojas desde la vista de detalles.')
            ->success()
            ->send();
    }
    
    private function createDefaultSheets(TalonarioItem $talonario): void
    {
        $defaultSheets = [
            ['sheet_type' => 'original', 'sheet_notes' => 'Hoja original'],
            ['sheet_type' => 'copia', 'sheet_notes' => 'Primera copia'],
            ['sheet_type' => 'copia', 'sheet_notes' => 'Segunda copia'],
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
    
    public function getWizardStep(): \Filament\Schemas\Components\Wizard\Step
    {
        return Step::make('Talonario')
            ->description('Talonarios con numeración secuencial y hojas múltiples')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Section::make('Información Básica')->schema([
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
                
                Section::make('Configuración')->schema([
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
            ]);
    }
}