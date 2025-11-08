<?php

namespace App\Filament\Resources\ProductionOrders\Handlers;

use App\Models\CustomItem;
use App\Models\DocumentItem;
use App\Models\ProductionOrder;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;

class CustomItemQuickHandler
{
    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Crear Item Personalizado para Orden de Producción')
                ->description('Agrega un item personalizado sin necesidad de cotización previa')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Item')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el trabajo a producir')
                        ->columnSpanFull(),

                    Grid::make(3)
                        ->schema([
                            Components\TextInput::make('quantity')
                                ->label('Cantidad a Producir')
                                ->numeric()
                                ->required()
                                ->default(1000)
                                ->minValue(1)
                                ->suffix('pzs'),

                            Components\TextInput::make('horizontal_size')
                                ->label('Ancho')
                                ->numeric()
                                ->required()
                                ->default(21.5)
                                ->suffix('cm')
                                ->minValue(1),

                            Components\TextInput::make('vertical_size')
                                ->label('Alto')
                                ->numeric()
                                ->required()
                                ->default(28)
                                ->suffix('cm')
                                ->minValue(1),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Components\TextInput::make('ink_front_count')
                                ->label('Tintas Frente')
                                ->numeric()
                                ->required()
                                ->default(4)
                                ->minValue(0)
                                ->maxValue(8)
                                ->suffix('colores'),

                            Components\TextInput::make('ink_back_count')
                                ->label('Tintas Reverso')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(8)
                                ->suffix('colores'),
                        ]),

                    Components\Textarea::make('notes')
                        ->label('Notas de Producción')
                        ->rows(2)
                        ->placeholder('Instrucciones especiales, detalles técnicos, etc.')
                        ->columnSpanFull(),

                    Components\Placeholder::make('custom_summary')
                        ->content(function ($get) {
                            $description = $get('description');
                            $quantity = $get('quantity') ?? 1000;
                            $width = $get('horizontal_size') ?? 21.5;
                            $height = $get('vertical_size') ?? 28;
                            $frontInks = $get('ink_front_count') ?? 4;
                            $backInks = $get('ink_back_count') ?? 0;

                            if (empty($description)) {
                                return '<div class="p-3 bg-gray-50 rounded text-gray-500">📝 Completa la descripción para ver el resumen</div>';
                            }

                            $totalInks = $frontInks + $backInks;
                            $content = '<div class="p-4 bg-blue-50 rounded space-y-2">';
                            $content .= '<h4 class="font-semibold text-blue-800">🏭 Resumen del Item de Producción</h4>';
                            $content .= '<div class="space-y-1 text-sm">';
                            $content .= '<div><strong>Descripción:</strong> '.e(substr($description, 0, 80)).(strlen($description) > 80 ? '...' : '').'</div>';
                            $content .= '<div><strong>Cantidad:</strong> '.number_format($quantity).' piezas</div>';
                            $content .= '<div><strong>Tamaño:</strong> '.$width.' × '.$height.' cm</div>';
                            $content .= '<div><strong>Tintas:</strong> Frente '.$frontInks.' + Reverso '.$backInks.' = '.$totalInks.' colores</div>';
                            $content .= '</div>';
                            $content .= '<div class="mt-3 p-3 bg-white rounded border border-blue-200">';
                            $content .= '<div class="text-sm text-gray-600 text-center">';
                            $content .= '⚠️ Este item será agregado como item personalizado a la orden de producción';
                            $content .= '</div>';
                            $content .= '</div>';
                            $content .= '</div>';

                            return $content;
                        })
                        ->html()
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function handleCreate(array $data, ProductionOrder $productionOrder): void
    {
        // Construir notas que incluyen datos técnicos + notas del usuario
        $technicalData = sprintf(
            "Datos técnicos:\n- Tamaño: %s × %s cm\n- Tintas: Frente %d + Reverso %d = %d total",
            $data['horizontal_size'],
            $data['vertical_size'],
            $data['ink_front_count'],
            $data['ink_back_count'],
            $data['ink_front_count'] + $data['ink_back_count']
        );

        $fullNotes = $technicalData;
        if (!empty($data['notes'])) {
            $fullNotes .= "\n\nNotas adicionales:\n".$data['notes'];
        }

        // Crear el CustomItem con los datos básicos
        $customItem = CustomItem::create([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => 0, // No tiene precio en este contexto de producción
            'notes' => $fullNotes,
        ]);

        // Crear un DocumentItem temporal (sin documento asociado)
        $documentItem = DocumentItem::create([
            'document_id' => null,
            'company_id' => auth()->user()->company_id,
            'itemable_type' => 'App\\Models\\CustomItem',
            'itemable_id' => $customItem->id,
            'description' => 'Personalizado: '.$customItem->description,
            'quantity' => $customItem->quantity,
            'unit_price' => 0,
            'total_price' => 0,
            'order_status' => 'available',
        ]);

        // Agregar el item a la orden con los datos de producción en la tabla pivot
        $productionOrder->documentItems()->attach($documentItem->id, [
            'process_type' => 'printing',
            'quantity_to_produce' => $data['quantity'],
            'item_status' => 'pending',
            'ink_front_count' => $data['ink_front_count'],
            'ink_back_count' => $data['ink_back_count'],
            'sheets_needed' => 0, // Se calculará después si es necesario
            'total_impressions' => 0, // Se calculará después si es necesario
            'produced_quantity' => 0,
            'rejected_quantity' => 0,
            'production_notes' => "Item personalizado - Tamaño: {$data['horizontal_size']}×{$data['vertical_size']} cm",
        ]);
    }

    public function getLabel(): string
    {
        return 'Item Personalizado';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-pencil-square';
    }

    public function getColor(): string
    {
        return 'warning';
    }

    public function getModalWidth(): string
    {
        return '5xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item personalizado agregado a producción';
    }
}
