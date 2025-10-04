<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\MagazineItem;
use App\Models\MagazinePage;
use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;

class MagazineItemHandler extends AbstractItemHandler
{
    protected $record;

    public function setRecord($record): self
    {
        $this->record = $record;
        return $this;
    }
    public function getEditForm($record): array
    {
        return [
            Section::make('Información Básica')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Revista')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe la revista: temática, características especiales, etc.'),

                    Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->default(100)
                        ->minValue(1)
                        ->suffix('revistas')
                        ->placeholder('100'),
                ]),

            Section::make('Dimensiones Revista Cerrada')
                ->schema([
                    Grid::make(2)->schema([
                        Components\TextInput::make('closed_width')
                            ->label('Ancho Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('21'),

                        Components\TextInput::make('closed_height')
                            ->label('Alto Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->minValue(0)
                            ->placeholder('29.7'),
                    ]),
                ]),

            Section::make('Encuadernación')
                ->schema([
                    Grid::make(2)->schema([
                        Components\Select::make('binding_type')
                            ->label('Tipo de Encuadernación')
                            ->required()
                            ->options([
                                'grapado' => 'Grapado',
                                'plegado' => 'Plegado',
                                'anillado' => 'Anillado',
                                'cosido' => 'Cosido',
                                'caballete' => 'Caballete',
                                'lomo' => 'Lomo',
                                'espiral' => 'Espiral',
                                'wire_o' => 'Wire-O',
                                'hotmelt' => 'Hot Melt',
                            ])
                            ->default('grapado')
                            ->searchable()
                            ->helperText('Seleccione el método de encuadernación'),

                        Components\Select::make('binding_side')
                            ->label('Lado de Encuadernación')
                            ->required()
                            ->options([
                                'arriba' => 'Arriba',
                                'izquierda' => 'Izquierda',
                                'derecha' => 'Derecha',
                                'abajo' => 'Abajo',
                            ])
                            ->default('izquierda')
                            ->helperText('Lado donde se aplicará la encuadernación'),
                    ]),
                ]),

            Section::make('Costos Adicionales')
                ->schema([
                    Grid::make(3)->schema([
                        Components\TextInput::make('design_value')
                            ->label('Valor Diseño')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0)
                            ->placeholder('0'),

                        Components\TextInput::make('transport_value')
                            ->label('Valor Transporte')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0)
                            ->placeholder('0'),

                        Components\TextInput::make('profit_percentage')
                            ->label('Porcentaje de Ganancia')
                            ->numeric()
                            ->default(25)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('25'),
                    ]),
                ]),

            Section::make('Notas')
                ->schema([
                    Components\Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Información adicional sobre la revista...'),
                ]),
        ];
    }

    public function fillForm($record): array
    {
        return [
            'description' => $record->itemable->description,
            'quantity' => $record->itemable->quantity,
            'closed_width' => $record->itemable->closed_width,
            'closed_height' => $record->itemable->closed_height,
            'binding_type' => $record->itemable->binding_type,
            'binding_side' => $record->itemable->binding_side,
            'design_value' => $record->itemable->design_value,
            'transport_value' => $record->itemable->transport_value,
            'profit_percentage' => $record->itemable->profit_percentage,
            'notes' => $record->itemable->notes,
        ];
    }

    public function handleUpdate($record, array $data): void
    {
        $record->itemable->update($data);
        $record->itemable->calculateAll();
        $record->update([
            'unit_price' => $record->itemable->final_price / $record->itemable->quantity,
            'total_price' => $record->itemable->final_price,
        ]);
    }

    public function getWizardSteps(): array
    {
        return [
            Step::make('Información Básica')
                ->description('Datos generales de la revista')
                ->icon('heroicon-o-book-open')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Revista')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Describe la revista: temática, características especiales, etc.'),

                    Grid::make(2)->schema([
                        Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->suffix('revistas'),

                        Components\TextInput::make('profit_percentage')
                            ->label('Margen de Ganancia')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->default(25),
                    ]),
                ]),

            Step::make('Configuración de Revista')
                ->description('Dimensiones y encuadernación')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Grid::make(2)->schema([
                        Components\TextInput::make('closed_width')
                            ->label('Ancho Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->default(21),

                        Components\TextInput::make('closed_height')
                            ->label('Alto Cerrado')
                            ->numeric()
                            ->required()
                            ->suffix('cm')
                            ->default(29.7),
                    ]),

                    Grid::make(2)->schema([
                        Components\Select::make('binding_type')
                            ->label('Tipo de Encuadernación')
                            ->required()
                            ->options([
                                'grapado' => 'Grapado',
                                'plegado' => 'Plegado',
                                'anillado' => 'Anillado',
                                'cosido' => 'Cosido',
                                'caballete' => 'Caballete',
                                'lomo' => 'Lomo',
                                'espiral' => 'Espiral',
                                'wire_o' => 'Wire-O',
                                'hotmelt' => 'Hot Melt',
                            ])
                            ->default('grapado')
                            ->searchable(),

                        Components\Select::make('binding_side')
                            ->label('Lado de Encuadernación')
                            ->required()
                            ->options([
                                'arriba' => 'Arriba',
                                'izquierda' => 'Izquierda',
                                'derecha' => 'Derecha',
                                'abajo' => 'Abajo',
                            ])
                            ->default('izquierda'),
                    ]),

                    Grid::make(3)->schema([
                        Components\TextInput::make('design_value')
                            ->label('Valor Diseño')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),

                        Components\TextInput::make('transport_value')
                            ->label('Valor Transporte')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),

                        Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(2)
                            ->placeholder('Información adicional...'),
                    ]),
                ]),

            Step::make('Configuración de Páginas')
                ->description('Define las páginas que tendrá la revista')
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    Components\Repeater::make('pages')
                        ->label('Páginas de la Revista')
                        ->schema([
                            Grid::make(3)->schema([
                                Components\Select::make('page_type')
                                    ->label('Tipo de Página')
                                    ->required()
                                    ->options([
                                        'portada' => '📖 Portada',
                                        'contraportada' => '📗 Contraportada',
                                        'interior' => '📄 Interior',
                                        'inserto' => '📋 Inserto',
                                        'separador' => '📑 Separador',
                                        'anexo' => '📎 Anexo',
                                    ])
                                    ->default('interior'),

                                Components\TextInput::make('page_quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->suffix('pág.'),

                                Components\TextInput::make('page_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                            ]),

                            Components\Textarea::make('description')
                                ->label('Descripción del Contenido')
                                ->required()
                                ->rows(2)
                                ->columnSpanFull()
                                ->placeholder('Describe el contenido de esta página...'),

                            Grid::make(2)->schema([
                                Components\Select::make('paper_id')
                                    ->label('Papel')
                                    ->options(function () {
                                        $companyId = auth()->user()->company_id ?? 1;
                                        return Paper::query()
                                            ->forTenant($companyId)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($paper) {
                                                $label = $paper->full_name ?: ($paper->code . ' - ' . $paper->name);
                                                return [$paper->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable(),

                                Components\Select::make('printing_machine_id')
                                    ->label('Máquina de Impresión')
                                    ->options(function () {
                                        $companyId = auth()->user()->company_id ?? 1;
                                        return PrintingMachine::query()
                                            ->forTenant($companyId)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($machine) {
                                                $label = $machine->name . ' - ' . ucfirst($machine->type);
                                                return [$machine->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable(),
                            ]),

                            Grid::make(4)->schema([
                                Components\TextInput::make('horizontal_size')
                                    ->label('Ancho')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->default(21),

                                Components\TextInput::make('vertical_size')
                                    ->label('Alto')
                                    ->numeric()
                                    ->required()
                                    ->suffix('cm')
                                    ->default(29.7),

                                Components\TextInput::make('ink_front_count')
                                    ->label('Tintas Frente')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->maxValue(6),

                                Components\TextInput::make('ink_back_count')
                                    ->label('Tintas Dorso')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(6),
                            ]),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->maxItems(20)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['page_type'] ?
                            '📄 ' . ucfirst($state['page_type']) . ' - ' . ($state['page_quantity'] ?? 1) . ' pág.' :
                            'Nueva Página'
                        )
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function getWizardStep(): Step
    {
        return $this->getWizardSteps()[0];
    }

    public function handleCreate(array $data): void
    {
        // Extraer datos de páginas
        $pagesData = $data['pages'] ?? [];
        unset($data['pages']);

        $magazine = MagazineItem::create(array_merge($data, [
            'company_id' => auth()->user()->company_id,
        ]));

        // Crear páginas si se proporcionaron
        if (!empty($pagesData)) {
            $this->createPagesFromWizardData($magazine, $pagesData);
        } else {
            // Crear página por defecto si no se especificaron
            $this->createDefaultPage($magazine);
        }

        // Recalcular costos después de crear páginas
        $magazine->calculateAll();
        $magazine->save();

        $this->record->documentItems()->create([
            'itemable_type' => MagazineItem::class,
            'itemable_id' => $magazine->id,
            'quantity' => $magazine->quantity,
            'unit_price' => $magazine->final_price / $magazine->quantity,
            'total_price' => $magazine->final_price,
            'order' => $this->record->documentItems()->max('order') + 1,
        ]);
    }

    private function createPagesFromWizardData(MagazineItem $magazine, array $pagesData): void
    {
        foreach ($pagesData as $pageData) {
            // Crear SimpleItem para cada página
            $simpleItem = SimpleItem::create([
                'company_id' => $magazine->company_id,
                'description' => $pageData['description'],
                'quantity' => $magazine->quantity * ($pageData['page_quantity'] ?? 1),
                'horizontal_size' => $pageData['horizontal_size'],
                'vertical_size' => $pageData['vertical_size'],
                'paper_id' => $pageData['paper_id'],
                'printing_machine_id' => $pageData['printing_machine_id'],
                'ink_front_count' => $pageData['ink_front_count'] ?? 1,
                'ink_back_count' => $pageData['ink_back_count'] ?? 0,
                'design_value' => $pageData['design_value'] ?? 0,
                'transport_value' => $pageData['transport_value'] ?? 0,
                'profit_percentage' => $pageData['profit_percentage'] ?? 25,
            ]);

            // Crear MagazinePage
            MagazinePage::create([
                'magazine_item_id' => $magazine->id,
                'simple_item_id' => $simpleItem->id,
                'page_type' => $pageData['page_type'],
                'page_order' => $pageData['page_order'] ?? 1,
                'page_quantity' => $pageData['page_quantity'] ?? 1,
            ]);
        }
    }

    private function createDefaultPage(MagazineItem $magazine): void
    {
        // Crear SimpleItem por defecto
        $simpleItem = SimpleItem::create([
            'company_id' => $magazine->company_id,
            'description' => 'Página interior de ' . $magazine->description,
            'quantity' => $magazine->quantity,
            'horizontal_size' => $magazine->closed_width,
            'vertical_size' => $magazine->closed_height,
            'paper_id' => Paper::where('company_id', $magazine->company_id)->first()?->id,
            'printing_machine_id' => PrintingMachine::where('company_id', $magazine->company_id)->first()?->id,
            'ink_front_count' => 1,
            'ink_back_count' => 0,
            'design_value' => 0,
            'transport_value' => 0,
            'profit_percentage' => 25,
        ]);

        // Crear MagazinePage por defecto
        MagazinePage::create([
            'magazine_item_id' => $magazine->id,
            'simple_item_id' => $simpleItem->id,
            'page_type' => 'interior',
            'page_order' => 1,
            'page_quantity' => 1,
        ]);
    }
}