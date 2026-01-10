<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\DigitalItem;

class DigitalItemHandler extends AbstractItemHandler
{
    public function getEditForm($record): array
    {
        $item = $record->itemable;
        $isSizeType = $item && $item->pricing_type === 'size';

        return [
            $this->makeSection('Editar Impresión Digital', 'Modificar cantidad y dimensiones del servicio')
                ->schema([
                    // Mostrar información del item maestro (solo lectura)
                    Components\Placeholder::make('master_info')
                        ->label('Información del Catálogo')
                        ->content(function () use ($item) {
                            if (!$item) return 'Item no encontrado';

                            $content = '<div class="space-y-1">';
                            $content .= '<div><strong>Servicio:</strong> ' . $item->description . '</div>';
                            $content .= '<div><strong>Tipo:</strong> ' . $item->pricing_type_name . '</div>';
                            $content .= '<div><strong>Precio:</strong> ' . $item->formatted_sale_price . '</div>';
                            $content .= '</div>';
                            return $content;
                        })
                        ->html()
                        ->columnSpanFull(),

                    // Campos editables del DocumentItem
                    $this->makeGrid($isSizeType ? 3 : 1)->schema(array_filter([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->suffix('unidades')
                            ->required()
                            ->live(),

                        $isSizeType ? $this->makeTextInput('width', 'Ancho')
                            ->suffix('cm')
                            ->numeric()
                            ->required()
                            ->live() : null,

                        $isSizeType ? $this->makeTextInput('height', 'Alto')
                            ->suffix('cm')
                            ->numeric()
                            ->required()
                            ->live() : null,
                    ])),

                    // Resumen de cálculo
                    Components\Placeholder::make('calculation_summary')
                        ->label('Cálculo Automático')
                        ->content(function ($get) use ($item) {
                            if (!$item) return 'No se puede calcular';

                            $quantity = $get('quantity') ?? 1;
                            $width = $get('width') ?? 0;
                            $height = $get('height') ?? 0;

                            $params = ['quantity' => $quantity];
                            if ($item->pricing_type === 'size') {
                                $params['width'] = $width;
                                $params['height'] = $height;
                            }

                            $total = $item->calculateTotalPrice($params);
                            $unitPrice = $quantity > 0 ? $total / $quantity : 0;

                            $content = '<div class="p-3 bg-blue-50 rounded space-y-1">';

                            if ($item->pricing_type === 'size' && $width > 0 && $height > 0) {
                                $area = ($width / 100) * ($height / 100);
                                $content .= '<div><strong>Área:</strong> ' . number_format($area, 4) . ' m²</div>';
                            }

                            $content .= '<div><strong>Precio Unitario:</strong> $' . number_format($unitPrice, 2) . '</div>';
                            $content .= '<div><strong>Total:</strong> $' . number_format($total, 2) . '</div>';
                            $content .= '</div>';

                            return $content;
                        })
                        ->html()
                        ->columnSpanFull(),

                    // Campos ocultos para mantener referencia
                    Components\Hidden::make('pricing_type'),
                    Components\Hidden::make('sale_price'),
                ])
        ];
    }
    
    public function fillForm($record): array
    {
        $item = $record->itemable;
        $data = $item ? $item->toArray() : [];

        // Agregar los campos del DocumentItem y del DigitalItem
        $data['width'] = $record->width;
        $data['height'] = $record->height;
        $data['quantity'] = $record->quantity;
        $data['description'] = $record->description;

        // Asegurar que pricing_type esté disponible para la visibilidad condicional
        if ($item) {
            $data['pricing_type'] = $item->pricing_type;
            $data['sale_price'] = $item->sale_price;
        }

        return $data;
    }
    
    public function handleUpdate($record, array $data): void
    {
        $item = $record->itemable;
        if (!$item) {
            return;
        }

        // NO modificar el DigitalItem maestro
        // Solo actualizar el DocumentItem con los datos del formulario

        $quantity = $data['quantity'] ?? $record->quantity;
        $width = $data['width'] ?? $record->width;
        $height = $data['height'] ?? $record->height;

        // Calcular precios usando el item maestro
        $params = ['quantity' => $quantity];
        if ($item->pricing_type === 'size') {
            $params['width'] = $width;
            $params['height'] = $height;
        }

        $totalPrice = $item->calculateTotalPrice($params);
        $unitPrice = $quantity > 0 ? $totalPrice / $quantity : 0;

        // Actualizar solo el DocumentItem
        $documentItemData = [
            'quantity' => $quantity,
            'width' => $width,
            'height' => $height,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'description' => '', // Forzar regeneración automática
        ];

        $record->update($documentItemData);

        // La descripción se regenerará automáticamente por el hook boot() en DocumentItem
    }
    
    public function getWizardStep(): Step
    {
        return Step::make('Impresión Digital')
            ->description('Servicios de impresión digital con valoración flexible')
            ->icon('heroicon-o-computer-desktop')
            ->schema([
                $this->makeSection('Información del Servicio')->schema([
                    Components\Textarea::make('description')
                        ->label('Descripción de la Impresión Digital')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('Ej: Diseño gráfico, Desarrollo web, Fotografía...'),
                        
                    $this->makeGrid(2)->schema([
                        $this->makeTextInput('quantity', 'Cantidad')
                            ->default(1)
                            ->suffix('unidades'),
                            
                        $this->makeSelect('pricing_type', 'Tipo de Valoración', [
                            'unit' => 'Por Unidad - Precio fijo',
                            'size' => 'Por Tamaño (m²) - Según dimensiones',
                        ])->default('unit')
                        ->live(),
                    ]),
                ]),
                
                $this->makeSection('Valoración')->schema([
                    Components\TextInput::make('sale_price')
                        ->label('Precio de Venta')
                        ->prefix('$')
                        ->numeric()
                        ->required()
                        ->helperText(function ($get) {
                            return $get('pricing_type') === 'size'
                                ? 'Precio por metro cuadrado'
                                : 'Precio por unidad';
                        }),

                    $this->makeGrid(2)
                        ->schema([
                            $this->makeTextInput('width', 'Ancho')
                                ->suffix('cm')
                                ->numeric()
                                ->required(fn ($get) => $get('pricing_type') === 'size'),

                            $this->makeTextInput('height', 'Alto')
                                ->suffix('cm')
                                ->numeric()
                                ->required(fn ($get) => $get('pricing_type') === 'size'),
                        ])
                        ->visible(fn ($get) => $get('pricing_type') === 'size'),
                ]),
            ]);
    }
}