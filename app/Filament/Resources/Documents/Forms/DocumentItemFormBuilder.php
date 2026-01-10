<?php

namespace App\Filament\Resources\Documents\Forms;

use App\Services\Documents\DocumentItemCalculationService;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class DocumentItemFormBuilder
{
    public function __construct(private $context = null)
    {
    }

    public function buildSchema(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Wizard\Step::make('Tipo de Item')
                    ->schema([
                        Select::make('item_type')
                            ->label('Tipo de Item')
                            ->options([
                                'simple' => 'Item Sencillo (montaje, papel, máquina, tintas)',
                                'talonario' => 'Talonario',
                                'magazine' => 'Revista',
                                'digital' => 'Digital',
                                'custom' => 'Personalizado',
                                'product' => 'Producto (desde inventario)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar datos cuando se cambia el tipo
                                $set('itemable_type', null);
                                $set('itemable_id', null);
                            }),
                    ]),

                Wizard\Step::make('Detalles del Item')
                    ->schema(function ($get) {
                        $itemType = $get('item_type');
                        return DocumentItemFormFactory::createForType($itemType, $this->context);
                    }),

                Wizard\Step::make('Confirmación')
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Resumen del Item')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('confirmation_summary')
                                    ->content(function ($get) {
                                        return $this->buildConfirmationSummary($get);
                                    })
                                    ->html()
                                    ->columnSpanFull(),
                            ])
                    ]),
            ])
                ->submitAction(new \Filament\Actions\Action('create'))
                ->nextAction(
                    fn (\Filament\Actions\Action $action) => $action->label('Siguiente →')
                )
                ->previousAction(
                    fn (\Filament\Actions\Action $action) => $action->label('← Anterior')
                )
                ->columnSpanFull(),
        ]);
    }

    private function buildConfirmationSummary($get): string
    {
        $itemType = $get('item_type');
        $quantity = $get('quantity') ?? 1;
        $unitPrice = $get('unit_price') ?? 0;
        $totalPrice = $get('total_price') ?? 0;

        $typeLabels = [
            'simple' => 'Item Sencillo',
            'digital' => 'Impresión Digital',
            'custom' => 'Item Personalizado',
            'product' => 'Producto',
            'magazine' => 'Revista',
            'talonario' => 'Talonario',
        ];

        $typeLabel = $typeLabels[$itemType] ?? 'Desconocido';

        $content = '<div class="p-4 bg-blue-50 rounded-lg space-y-3">';
        $content .= '<h3 class="text-lg font-semibold text-blue-800">📋 Resumen del Item</h3>';

        $content .= '<div class="grid grid-cols-2 gap-4 text-sm">';
        $content .= '<div><strong>Tipo:</strong> ' . $typeLabel . '</div>';
        $content .= '<div><strong>Cantidad:</strong> ' . number_format($quantity) . ' unidades</div>';
        $content .= '<div><strong>Precio Unitario:</strong> $' . number_format($unitPrice, 2) . '</div>';
        $content .= '<div><strong>Total:</strong> $' . number_format($totalPrice, 2) . '</div>';
        $content .= '</div>';

        // Información específica por tipo
        switch ($itemType) {
            case 'custom':
                $description = $get('description');
                if ($description) {
                    $content .= '<div class="mt-3 p-2 bg-white rounded border">';
                    $content .= '<strong>Descripción:</strong> ' . e($description);
                    $content .= '</div>';
                }
                break;

            case 'digital':
                $itemId = $get('itemable_id');
                if ($itemId) {
                    $item = \App\Models\DigitalItem::find($itemId);
                    if ($item) {
                        $content .= '<div class="mt-3 p-2 bg-white rounded border">';
                        $content .= '<strong>Impresión Digital:</strong> ' . e($item->description);
                        $content .= '</div>';
                    }
                }
                break;

            case 'product':
                $productId = $get('itemable_id');
                if ($productId) {
                    $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($productId);
                    if ($product) {
                        $content .= '<div class="mt-3 p-2 bg-white rounded border">';
                        $content .= '<strong>Producto:</strong> ' . e($product->name);
                        $content .= '</div>';
                    }
                }
                break;
        }

        $content .= '<div class="mt-4 p-3 bg-green-100 rounded border border-green-300">';
        $content .= '<div class="text-green-800 font-semibold">✅ Item listo para agregar a la cotización</div>';
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    public function calculateFinishingCost($set, $get): void
    {
        DocumentItemCalculationService::calculateFinishingCost($set, $get);
    }

    public function calculateSimpleFinishingCost($set, $get): void
    {
        DocumentItemCalculationService::calculateSimpleFinishingCost($set, $get);
    }

    public function recalculateItemTotal($set, $get): void
    {
        DocumentItemCalculationService::recalculateItemTotal($set, $get);
    }

    public function calculateProductTotal($get, $set): void
    {
        DocumentItemCalculationService::recalculateProductTotal($set, $get);
    }
}