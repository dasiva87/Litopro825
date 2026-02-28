<?php

namespace App\Filament\Resources\PurchaseOrders\Handlers;

use App\Models\PurchaseOrder;
use App\Models\CustomItem;
use App\Models\DocumentItem;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;

class CustomItemQuickHandler
{
    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Crear Item Personalizado para Orden')
                ->description('Agrega un item con precios manuales directamente a esta orden de pedido')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Item')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el producto o servicio personalizado')
                        ->columnSpanFull(),

                    Grid::make(3)
                        ->schema([
                            Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $unitPrice = $get('unit_price') ?? 0;
                                    $total = $state * $unitPrice;
                                    $set('total_price', number_format($total, 2, '.', ''));
                                }),

                            Components\TextInput::make('unit_price')
                                ->label('Precio Unitario')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->step(0.01)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $quantity = $get('quantity') ?? 1;
                                    $total = $quantity * $state;
                                    $set('total_price', number_format($total, 2, '.', ''));
                                }),

                            Components\TextInput::make('total_price')
                                ->label('Precio Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false),
                        ]),

                    Components\Textarea::make('notes')
                        ->label('Notas Adicionales')
                        ->rows(2)
                        ->placeholder('Notas sobre este item en la orden (opcional)')
                        ->columnSpanFull(),

                    Components\Placeholder::make('custom_summary')
                        ->content(function ($get) {
                            $description = $get('description');
                            $quantity = $get('quantity') ?? 1;
                            $unitPrice = $get('unit_price') ?? 0;
                            $totalPrice = $quantity * $unitPrice;

                            if (empty($description)) {
                                return '<div class="p-3 bg-gray-50 rounded text-gray-500">📝 Completa la descripción para ver el resumen</div>';
                            }

                            $content = '<div class="p-4 bg-green-50 rounded space-y-2">';
                            $content .= '<h4 class="font-semibold text-green-800">📋 Resumen del Item</h4>';
                            $content .= '<div class="space-y-1 text-sm">';
                            $content .= '<div><strong>Descripción:</strong> '.e(substr($description, 0, 80)).(strlen($description) > 80 ? '...' : '').'</div>';
                            $content .= '<div><strong>Cantidad:</strong> '.number_format($quantity).' unidades</div>';
                            $content .= '<div><strong>Precio unitario:</strong> $'.number_format($unitPrice, 2).'</div>';
                            $content .= '</div>';
                            $content .= '<div class="mt-3 p-3 bg-white rounded border border-green-200">';
                            $content .= '<div class="text-lg font-bold text-green-600 text-center">';
                            $content .= '💵 TOTAL: $'.number_format($totalPrice, 2);
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

    public function handleCreate(array $data, PurchaseOrder $order): void
    {
        // Crear el CustomItem
        $customItem = CustomItem::create([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'notes' => $data['notes'] ?? null,
        ]);

        // Crear un DocumentItem temporal (sin documento asociado, solo para la orden)
        $documentItem = DocumentItem::create([
            'document_id' => null, // Sin documento asociado
            'company_id' => auth()->user()->company_id,
            'itemable_type' => 'App\\Models\\CustomItem',
            'itemable_id' => $customItem->id,
            'description' => 'Personalizado: '.$customItem->description,
            'quantity' => $customItem->quantity,
            'unit_price' => $customItem->unit_price,
            'total_price' => $customItem->total_price,
            'order_status' => 'ordered', // Ya está en una orden
        ]);

        // Agregar el item a la orden usando la relación many-to-many
        $order->purchaseOrderItems()->create([
            'document_item_id' => $documentItem->id,
            'paper_id' => null,
            'paper_description' => $customItem->description,
            'quantity_ordered' => $customItem->quantity,
            'sheets_quantity' => 0,
            'cut_width' => null,
            'cut_height' => null,
            'unit_price' => $customItem->unit_price,
            'total_price' => $customItem->total_price,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        // Recalcular total de la orden
        $order->recalculateTotal();
    }

    public function getLabel(): string
    {
        return 'Item Personalizado Rápido';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-pencil-square';
    }

    public function getColor(): string
    {
        return 'secondary';
    }

    public function getModalWidth(): string
    {
        return '4xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item personalizado agregado a la orden';
    }
}
