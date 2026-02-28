<?php

namespace App\Filament\Resources\CollectionAccounts\Handlers;

use App\Models\CollectionAccount;
use App\Models\CustomItem;
use App\Models\DocumentItem;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;

class CustomItemQuickHandler
{
    public function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Crear Item Personalizado para Cuenta de Cobro')
                ->description('Agrega un item con precios manuales directamente a esta cuenta de cobro')
                ->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción del Item')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe el producto o servicio a facturar')
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
                        ->placeholder('Notas sobre este item en la cuenta (opcional)')
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

                            $content = '<div class="p-4 bg-blue-50 rounded space-y-2">';
                            $content .= '<h4 class="font-semibold text-blue-800">💰 Resumen del Item a Cobrar</h4>';
                            $content .= '<div class="space-y-1 text-sm">';
                            $content .= '<div><strong>Descripción:</strong> '.e(substr($description, 0, 80)).(strlen($description) > 80 ? '...' : '').'</div>';
                            $content .= '<div><strong>Cantidad:</strong> '.number_format($quantity).' unidades</div>';
                            $content .= '<div><strong>Precio unitario:</strong> $'.number_format($unitPrice, 2).'</div>';
                            $content .= '</div>';
                            $content .= '<div class="mt-3 p-3 bg-white rounded border border-blue-200">';
                            $content .= '<div class="text-lg font-bold text-blue-600 text-center">';
                            $content .= '💵 TOTAL A COBRAR: $'.number_format($totalPrice, 2);
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

    public function handleCreate(array $data, CollectionAccount $account): void
    {
        // Crear el CustomItem
        $customItem = CustomItem::create([
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'notes' => $data['notes'] ?? null,
        ]);

        // Crear un DocumentItem temporal (sin documento asociado, solo para la cuenta)
        $documentItem = DocumentItem::create([
            'document_id' => null, // Sin documento asociado
            'company_id' => auth()->user()->company_id,
            'itemable_type' => 'App\\Models\\CustomItem',
            'itemable_id' => $customItem->id,
            'description' => 'Personalizado: '.$customItem->description,
            'quantity' => $customItem->quantity,
            'unit_price' => $customItem->unit_price,
            'total_price' => $customItem->total_price,
            'order_status' => 'available', // Disponible (valores permitidos: available, in_cart, ordered, received)
        ]);

        // Agregar el item a la cuenta usando la relación many-to-many
        $account->documentItems()->attach($documentItem->id, [
            'quantity_ordered' => $customItem->quantity,
            'unit_price' => $customItem->unit_price,
            'total_price' => $customItem->total_price,
            'status' => 'pending',
        ]);

        // Recalcular total de la cuenta
        $account->recalculateTotal();
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
        return 'primary';
    }

    public function getModalWidth(): string
    {
        return '4xl';
    }

    public function getSuccessNotificationTitle(): string
    {
        return 'Item personalizado agregado a la cuenta de cobro';
    }
}
