<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Models\MagazineItem;
use App\Models\MagazinePage;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\SimpleItem;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;

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
            // Resumen de precios - AL INICIO para que siempre esté visible
            Section::make('💰 Resumen de Precios')
                ->description('Vista previa del cálculo total de la revista')
                ->schema([
                    Components\Placeholder::make('price_summary')
                        ->label('')
                        ->content(function () use ($record) {
                            return $this->getMagazinePriceSummary($record);
                        })
                        ->html()
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->collapsible()
                ->visible(fn () => $record !== null && $record->itemable !== null),

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

            Section::make('Páginas de la Revista')
                ->description('Edita las páginas que componen la revista')
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    Components\Repeater::make('pages')
                        ->label('Páginas')
                        ->schema([
                            // Información de la Página
                            Section::make('📄 Información de la Página')
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
                                            ->label('Cantidad de Páginas')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('pág.')
                                            ->helperText('Número de páginas iguales'),

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
                                ])->collapsible()->collapsed(false),

                            // Dimensiones y Formato
                            Section::make('📐 Dimensiones y Formato')
                                ->schema([
                                    Grid::make(4)->schema([
                                        Components\TextInput::make('horizontal_size')
                                            ->label('Ancho del Trabajo')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->default(21),

                                        Components\TextInput::make('vertical_size')
                                            ->label('Alto del Trabajo')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->default(29.7),

                                        Components\TextInput::make('sobrante_papel')
                                            ->label('Sobrante')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->suffix('unidades')
                                            ->helperText('Desperdicios (si >100 se cobra)'),

                                        Components\Placeholder::make('area_calc')
                                            ->label('Área')
                                            ->content(fn ($get) => $get('horizontal_size') && $get('vertical_size')
                                                ? number_format($get('horizontal_size') * $get('vertical_size'), 2).' cm²'
                                                : '-'
                                            ),
                                    ]),
                                ])->collapsible(),

                            // Papel y Máquina
                            Section::make('📄 Papel y Máquina')
                                ->schema([
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
                                                        $label = $paper->full_name ?: ($paper->code.' - '.$paper->name);

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
                                                        $label = $machine->name.' - '.ucfirst($machine->type);

                                                        return [$machine->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable(),
                                    ]),
                                ])->collapsible(),

                            // Tintas
                            Section::make('🎨 Tintas')
                                ->schema([
                                    Grid::make(3)->schema([
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

                                        Components\Toggle::make('front_back_plate')
                                            ->label('Placa Frente/Dorso')
                                            ->default(false)
                                            ->helperText('Usar la misma placa para ambas caras'),
                                    ]),
                                ])->collapsible(),

                            // Montaje
                            Section::make('📦 Montaje')
                                ->schema([
                                    Components\Select::make('mounting_type')
                                        ->label('Tipo de Montaje')
                                        ->options([
                                            'automatic' => '🤖 Automático (calculado por el sistema)',
                                            'manual' => '✋ Manual (dimensiones personalizadas)',
                                        ])
                                        ->default('automatic')
                                        ->live()
                                        ->helperText('Automático: sistema calcula el mejor montaje. Manual: defines dimensiones del pliego'),

                                    Grid::make(2)->schema([
                                        Components\TextInput::make('custom_paper_width')
                                            ->label('Ancho del Pliego Personalizado')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->visible(fn ($get) => $get('mounting_type') === 'manual')
                                            ->helperText('Ancho del pliego para montaje manual'),

                                        Components\TextInput::make('custom_paper_height')
                                            ->label('Alto del Pliego Personalizado')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->visible(fn ($get) => $get('mounting_type') === 'manual')
                                            ->helperText('Alto del pliego para montaje manual'),
                                    ]),
                                ])->collapsible(),

                            // Costos Adicionales
                            Section::make('💰 Costos Adicionales')
                                ->schema([
                                    Grid::make(5)->schema([
                                        Components\TextInput::make('cutting_cost')
                                            ->label('Costo Corte')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

                                        Components\TextInput::make('mounting_cost')
                                            ->label('Costo Montaje')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

                                        Components\TextInput::make('rifle_value')
                                            ->label('Valor Rifle')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

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
                                    ]),
                                ])->collapsible(),

                            // Ganancia
                            Section::make('📊 Ganancia')
                                ->schema([
                                    Components\TextInput::make('profit_percentage')
                                        ->label('Porcentaje de Ganancia')
                                        ->numeric()
                                        ->default(25)
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->placeholder('25')
                                        ->helperText('Porcentaje de ganancia sobre el costo de esta página'),
                                ])->collapsible(),
                        ])
                        ->minItems(1)
                        ->maxItems(20)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['page_type']) ?
                            '📄 '.ucfirst($state['page_type']).' - '.($state['page_quantity'] ?? 1).' pág.' :
                            'Nueva Página'
                        )
                        ->columnSpanFull()
                        ->reorderable()
                        ->cloneable()
                        ->defaultItems(1),
                ]),
        ];
    }

    public function fillForm($record): array
    {
        // Cargar las páginas con sus SimpleItems relacionados
        $pages = $record->itemable->pages()
            ->with('simpleItem')
            ->orderBy('page_order')
            ->get()
            ->map(function ($page) {
                $simpleItem = $page->simpleItem;

                return [
                    'id' => $page->id,
                    'page_type' => $page->page_type,
                    'page_quantity' => $page->page_quantity,
                    'page_order' => $page->page_order,
                    'description' => $simpleItem?->description ?? '',

                    // Dimensiones
                    'horizontal_size' => $simpleItem?->horizontal_size,
                    'vertical_size' => $simpleItem?->vertical_size,
                    'sobrante_papel' => $simpleItem?->sobrante_papel ?? 0,

                    // Papel y Máquina
                    'paper_id' => $simpleItem?->paper_id,
                    'printing_machine_id' => $simpleItem?->printing_machine_id,

                    // Tintas
                    'ink_front_count' => $simpleItem?->ink_front_count ?? 1,
                    'ink_back_count' => $simpleItem?->ink_back_count ?? 0,
                    'front_back_plate' => $simpleItem?->front_back_plate ?? false,

                    // Montaje
                    'mounting_type' => $simpleItem?->mounting_type ?? 'automatic',
                    'custom_paper_width' => $simpleItem?->custom_paper_width,
                    'custom_paper_height' => $simpleItem?->custom_paper_height,

                    // Costos Adicionales
                    'cutting_cost' => $simpleItem?->cutting_cost ?? 0,
                    'mounting_cost' => $simpleItem?->mounting_cost ?? 0,
                    'rifle_value' => $simpleItem?->rifle_value ?? 0,
                    'design_value' => $simpleItem?->design_value ?? 0,
                    'transport_value' => $simpleItem?->transport_value ?? 0,

                    // Ganancia
                    'profit_percentage' => $simpleItem?->profit_percentage ?? 25,

                    'simple_item_id' => $page->simple_item_id,
                ];
            })
            ->toArray();

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
            'pages' => $pages,
        ];
    }

    public function handleUpdate($record, array $data): void
    {
        // Extraer datos de páginas
        $pagesData = $data['pages'] ?? [];
        unset($data['pages']);

        // Actualizar datos básicos del MagazineItem
        $record->itemable->update($data);

        // Procesar páginas si se proporcionaron
        if (! empty($pagesData)) {
            $this->updatePages($record->itemable, $pagesData);
        }

        // Recalcular costos después de actualizar páginas
        $record->itemable->calculateAll();
        $record->itemable->save();

        // Actualizar DocumentItem
        $record->update([
            'unit_price' => $record->itemable->final_price / $record->itemable->quantity,
            'total_price' => $record->itemable->final_price,
        ]);
    }

    /**
     * Actualizar páginas existentes y crear/eliminar según sea necesario
     */
    private function updatePages(MagazineItem $magazine, array $pagesData): void
    {
        $existingPageIds = [];

        foreach ($pagesData as $pageData) {
            // Si tiene ID, es una página existente - actualizar
            if (isset($pageData['id']) && $pageData['id']) {
                $page = $magazine->pages()->find($pageData['id']);

                if ($page && $page->simpleItem) {
                    // Actualizar SimpleItem con TODOS los campos
                    $page->simpleItem->update([
                        'description' => $pageData['description'],
                        'quantity' => $magazine->quantity * ($pageData['page_quantity'] ?? 1),

                        // Dimensiones
                        'horizontal_size' => $pageData['horizontal_size'],
                        'vertical_size' => $pageData['vertical_size'],
                        'sobrante_papel' => $pageData['sobrante_papel'] ?? 0,

                        // Papel y Máquina
                        'paper_id' => $pageData['paper_id'],
                        'printing_machine_id' => $pageData['printing_machine_id'],

                        // Tintas
                        'ink_front_count' => $pageData['ink_front_count'] ?? 1,
                        'ink_back_count' => $pageData['ink_back_count'] ?? 0,
                        'front_back_plate' => $pageData['front_back_plate'] ?? false,

                        // Montaje
                        'mounting_type' => $pageData['mounting_type'] ?? 'automatic',
                        'custom_paper_width' => $pageData['custom_paper_width'] ?? null,
                        'custom_paper_height' => $pageData['custom_paper_height'] ?? null,

                        // Costos Adicionales
                        'cutting_cost' => $pageData['cutting_cost'] ?? 0,
                        'mounting_cost' => $pageData['mounting_cost'] ?? 0,
                        'rifle_value' => $pageData['rifle_value'] ?? 0,
                        'design_value' => $pageData['design_value'] ?? 0,
                        'transport_value' => $pageData['transport_value'] ?? 0,

                        // Ganancia
                        'profit_percentage' => $pageData['profit_percentage'] ?? 25,
                    ]);

                    // Actualizar MagazinePage
                    $page->update([
                        'page_type' => $pageData['page_type'],
                        'page_quantity' => $pageData['page_quantity'],
                        'page_order' => $pageData['page_order'],
                    ]);

                    $existingPageIds[] = $page->id;
                }
            } else {
                // Es una página nueva - crear con TODOS los campos
                $simpleItem = SimpleItem::create([
                    'company_id' => $magazine->company_id,
                    'description' => $pageData['description'],
                    'quantity' => $magazine->quantity * ($pageData['page_quantity'] ?? 1),

                    // Dimensiones
                    'horizontal_size' => $pageData['horizontal_size'],
                    'vertical_size' => $pageData['vertical_size'],
                    'sobrante_papel' => $pageData['sobrante_papel'] ?? 0,

                    // Papel y Máquina
                    'paper_id' => $pageData['paper_id'],
                    'printing_machine_id' => $pageData['printing_machine_id'],

                    // Tintas
                    'ink_front_count' => $pageData['ink_front_count'] ?? 1,
                    'ink_back_count' => $pageData['ink_back_count'] ?? 0,
                    'front_back_plate' => $pageData['front_back_plate'] ?? false,

                    // Montaje
                    'mounting_type' => $pageData['mounting_type'] ?? 'automatic',
                    'custom_paper_width' => $pageData['custom_paper_width'] ?? null,
                    'custom_paper_height' => $pageData['custom_paper_height'] ?? null,

                    // Costos Adicionales
                    'cutting_cost' => $pageData['cutting_cost'] ?? 0,
                    'mounting_cost' => $pageData['mounting_cost'] ?? 0,
                    'rifle_value' => $pageData['rifle_value'] ?? 0,
                    'design_value' => $pageData['design_value'] ?? 0,
                    'transport_value' => $pageData['transport_value'] ?? 0,

                    // Ganancia
                    'profit_percentage' => $pageData['profit_percentage'] ?? 25,
                ]);

                $newPage = $magazine->pages()->create([
                    'simple_item_id' => $simpleItem->id,
                    'page_type' => $pageData['page_type'],
                    'page_quantity' => $pageData['page_quantity'],
                    'page_order' => $pageData['page_order'],
                ]);

                $existingPageIds[] = $newPage->id;
            }
        }

        // Eliminar páginas que ya no están en el formulario
        $pagesToDelete = $magazine->pages()->whereNotIn('id', $existingPageIds)->get();
        foreach ($pagesToDelete as $page) {
            // Eliminar el SimpleItem asociado
            if ($page->simpleItem) {
                $page->simpleItem->delete();
            }
            // Eliminar la página
            $page->delete();
        }
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
                            // Información de la Página
                            Section::make('📄 Información de la Página')
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
                                            ->label('Cantidad de Páginas')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('pág.')
                                            ->helperText('Número de páginas iguales'),

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
                                ])->collapsible()->collapsed(false),

                            // Dimensiones y Formato
                            Section::make('📐 Dimensiones y Formato')
                                ->schema([
                                    Grid::make(4)->schema([
                                        Components\TextInput::make('horizontal_size')
                                            ->label('Ancho del Trabajo')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->default(21),

                                        Components\TextInput::make('vertical_size')
                                            ->label('Alto del Trabajo')
                                            ->numeric()
                                            ->required()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->default(29.7),

                                        Components\TextInput::make('sobrante_papel')
                                            ->label('Sobrante')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->suffix('unidades')
                                            ->helperText('Desperdicios (si >100 se cobra)'),

                                        Components\Placeholder::make('area_calc')
                                            ->label('Área')
                                            ->content(fn ($get) => $get('horizontal_size') && $get('vertical_size')
                                                ? number_format($get('horizontal_size') * $get('vertical_size'), 2).' cm²'
                                                : '-'
                                            ),
                                    ]),
                                ])->collapsible(),

                            // Papel y Máquina
                            Section::make('📄 Papel y Máquina')
                                ->schema([
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
                                                        $label = $paper->full_name ?: ($paper->code.' - '.$paper->name);

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
                                                        $label = $machine->name.' - '.ucfirst($machine->type);

                                                        return [$machine->id => $label];
                                                    })
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable(),
                                    ]),
                                ])->collapsible(),

                            // Tintas
                            Section::make('🎨 Tintas')
                                ->schema([
                                    Grid::make(3)->schema([
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

                                        Components\Toggle::make('front_back_plate')
                                            ->label('Placa Frente/Dorso')
                                            ->default(false)
                                            ->helperText('Usar la misma placa para ambas caras'),
                                    ]),
                                ])->collapsible(),

                            // Montaje
                            Section::make('📦 Montaje')
                                ->schema([
                                    Components\Select::make('mounting_type')
                                        ->label('Tipo de Montaje')
                                        ->options([
                                            'automatic' => '🤖 Automático (calculado por el sistema)',
                                            'manual' => '✋ Manual (dimensiones personalizadas)',
                                        ])
                                        ->default('automatic')
                                        ->live()
                                        ->helperText('Automático: sistema calcula el mejor montaje. Manual: defines dimensiones del pliego'),

                                    Grid::make(2)->schema([
                                        Components\TextInput::make('custom_paper_width')
                                            ->label('Ancho del Pliego Personalizado')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->visible(fn ($get) => $get('mounting_type') === 'manual')
                                            ->helperText('Ancho del pliego para montaje manual'),

                                        Components\TextInput::make('custom_paper_height')
                                            ->label('Alto del Pliego Personalizado')
                                            ->numeric()
                                            ->suffix('cm')
                                            ->step(0.1)
                                            ->visible(fn ($get) => $get('mounting_type') === 'manual')
                                            ->helperText('Alto del pliego para montaje manual'),
                                    ]),
                                ])->collapsible(),

                            // Costos Adicionales
                            Section::make('💰 Costos Adicionales')
                                ->schema([
                                    Grid::make(5)->schema([
                                        Components\TextInput::make('cutting_cost')
                                            ->label('Costo Corte')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

                                        Components\TextInput::make('mounting_cost')
                                            ->label('Costo Montaje')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

                                        Components\TextInput::make('rifle_value')
                                            ->label('Valor Rifle')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->placeholder('0'),

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
                                    ]),
                                ])->collapsible(),

                            // Ganancia
                            Section::make('📊 Ganancia')
                                ->schema([
                                    Components\TextInput::make('profit_percentage')
                                        ->label('Porcentaje de Ganancia')
                                        ->numeric()
                                        ->default(25)
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->placeholder('25')
                                        ->helperText('Porcentaje de ganancia sobre el costo de esta página'),
                                ])->collapsible(),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->maxItems(20)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['page_type']) ?
                            '📄 '.ucfirst($state['page_type']).' - '.($state['page_quantity'] ?? 1).' pág.' :
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
        if (! empty($pagesData)) {
            $this->createPagesFromWizardData($magazine, $pagesData);
        } else {
            // Crear página por defecto si no se especificaron
            $this->createDefaultPage($magazine);
        }

        // Recalcular costos después de crear páginas
        $magazine->calculateAll();
        $magazine->save();

        $this->record->items()->create([
            'itemable_type' => MagazineItem::class,
            'itemable_id' => $magazine->id,
            'quantity' => $magazine->quantity,
            'unit_price' => $magazine->final_price / $magazine->quantity,
            'total_price' => $magazine->final_price,
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
            'description' => 'Página interior de '.$magazine->description,
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

    /**
     * Generar resumen de precios de la revista
     */
    private function getMagazinePriceSummary($record): string
    {
        try {
            if (! $record || ! $record->itemable) {
                return '<div class="p-4 bg-gray-50 rounded text-center">
                    <div class="text-sm text-gray-500">No hay datos de la revista</div>
                </div>';
            }

            $magazine = $record->itemable;

            // Cargar relaciones necesarias
            $magazine->load(['pages.simpleItem', 'finishings']);

            // Validar datos mínimos
            if (! $magazine->quantity || $magazine->pages->count() === 0) {
                return '<div class="p-4 bg-yellow-50 rounded text-center">
                    <div class="text-sm text-yellow-700">⚠️ Datos incompletos</div>
                    <div class="text-xs text-yellow-600 mt-1">Se requiere cantidad y al menos una página</div>
                </div>';
            }

            // Obtener desglose detallado usando el servicio
            $calculator = new \App\Services\MagazineCalculatorService;
            $breakdown = $calculator->getDetailedBreakdown($magazine);

            $content = '<div class="space-y-3">';

            // Métricas principales
            $content .= '<div class="p-3 bg-blue-50 rounded border border-blue-200">';
            $content .= '<div class="text-xs font-medium text-blue-700 mb-1">REVISTA</div>';
            $content .= '<div class="grid grid-cols-4 gap-2 text-xs">';
            $content .= '<div><span class="text-gray-600">Cantidad:</span> <strong>'.$magazine->quantity.'</strong></div>';
            $content .= '<div><span class="text-gray-600">Páginas:</span> <strong>'.$breakdown['metrics']['total_pages'].'</strong></div>';
            $content .= '<div><span class="text-gray-600">Encuadernación:</span> <strong>'.ucfirst($breakdown['metrics']['binding_type']).'</strong></div>';
            $content .= '<div><span class="text-gray-600">Lado:</span> <strong>'.ucfirst($breakdown['metrics']['binding_side']).'</strong></div>';
            $content .= '</div>';
            $content .= '</div>';

            // Desglose de costos por página
            if (! empty($breakdown['pages']['items'])) {
                $content .= '<div class="p-2 bg-gray-50 rounded">';
                $content .= '<div class="text-xs font-medium text-gray-700 mb-1">PÁGINAS</div>';
                $content .= '<div class="space-y-1">';
                foreach ($breakdown['pages']['items'] as $pageBreakdown) {
                    $content .= '<div class="flex justify-between text-xs">';
                    $content .= '<span class="text-gray-600">'.$pageBreakdown['page_type'].' (×'.$pageBreakdown['quantity'].')</span>';
                    $content .= '<span>$'.number_format($pageBreakdown['total_cost'], 0).'</span>';
                    $content .= '</div>';
                }
                $content .= '</div>';
                $content .= '</div>';
            }

            // Desglose de costos
            $content .= '<div class="space-y-1">';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Páginas</span>';
            $content .= '<span>$'.number_format($breakdown['pages']['total'], 0).'</span>';
            $content .= '</div>';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Encuadernación</span>';
            $content .= '<span>$'.number_format($breakdown['binding']['total'], 0).'</span>';
            $content .= '</div>';
            $content .= '<div class="flex justify-between text-sm">';
            $content .= '<span class="text-gray-600">Armado</span>';
            $content .= '<span>$'.number_format($breakdown['assembly']['total'], 0).'</span>';
            $content .= '</div>';

            if ($breakdown['finishings']['total'] > 0) {
                $content .= '<div class="flex justify-between text-sm text-purple-600">';
                $content .= '<span>Acabados</span>';
                $content .= '<span>+$'.number_format($breakdown['finishings']['total'], 0).'</span>';
                $content .= '</div>';
            }

            if ($breakdown['additional_costs']['design'] > 0) {
                $content .= '<div class="flex justify-between text-sm text-gray-600">';
                $content .= '<span>Diseño</span>';
                $content .= '<span>+$'.number_format($breakdown['additional_costs']['design'], 0).'</span>';
                $content .= '</div>';
            }

            if ($breakdown['additional_costs']['transport'] > 0) {
                $content .= '<div class="flex justify-between text-sm text-gray-600">';
                $content .= '<span>Transporte</span>';
                $content .= '<span>+$'.number_format($breakdown['additional_costs']['transport'], 0).'</span>';
                $content .= '</div>';
            }

            $content .= '<div class="flex justify-between text-sm text-green-600 border-t pt-1">';
            $content .= '<span>Ganancia ('.$breakdown['summary']['profit_percentage'].'%)</span>';
            $content .= '<span>+$'.number_format($breakdown['summary']['profit_amount'], 0).'</span>';
            $content .= '</div>';
            $content .= '</div>';

            // Total
            $content .= '<div class="flex justify-between items-center font-bold text-lg border-t-2 pt-2 mt-2">';
            $content .= '<span>PRECIO TOTAL</span>';
            $content .= '<span class="text-blue-600">$'.number_format($breakdown['summary']['final_price'], 0).'</span>';
            $content .= '</div>';

            $content .= '<div class="text-center text-xs text-gray-500 mt-1">';
            $content .= 'Precio unitario: <strong>$'.number_format($breakdown['summary']['unit_price'], 2).'</strong>';
            $content .= '</div>';

            $content .= '</div>';

            return $content;

        } catch (\Exception $e) {
            return '<div class="p-4 bg-red-50 rounded">
                <div class="text-sm text-red-700">Error al calcular</div>
                <div class="text-xs text-red-600 mt-1">'.$e->getMessage().'</div>
            </div>';
        }
    }
}
