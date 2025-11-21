<?php

namespace App\Filament\Resources\Documents\Forms;

use App\Filament\Resources\SimpleItems\Schemas\SimpleItemForm;
use App\Filament\Resources\TalonarioItems\Schemas\TalonarioItemForm;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Schemas\Schema;

class DocumentItemFormFactory
{
    public static function createForType(string $itemType, $context = null): array
    {
        return match($itemType) {
            'simple' => static::createSimpleItemSchema(),
            'digital' => static::createDigitalItemSchema($context),
            'custom' => CustomItemDocumentForm::schema(),
            'product' => ProductDocumentForm::schema($context),
            'magazine' => static::createMagazineItemSchema($context),
            'talonario' => static::createTalonarioItemSchema($context),
            default => [],
        };
    }

    private static function createSimpleItemSchema(): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\SimpleItem'),

            // Incluir formulario de SimpleItem inline
            Group::make()
                ->schema(SimpleItemForm::configure(new Schema)->getComponents())
                ->columnSpanFull(),
        ];
    }

    private static function createDigitalItemSchema($context): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\DigitalItem'),

            \Filament\Schemas\Components\Section::make('Seleccionar Item Digital')
                ->description('Elige un item digital existente y especifica parámetros')
                ->schema([
                    Forms\Components\Select::make('itemable_id')
                        ->label('Item Digital')
                        ->options(function () {
                            return \App\Models\DigitalItem::where('company_id', auth()->user()->company_id)
                                ->where('active', true)
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    return [$item->id => $item->code.' - '.$item->description.' ('.$item->pricing_type_name.')'];
                                });
                        })
                        ->searchable(['code', 'description'])
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    \Filament\Schemas\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->suffix('unidades')
                                ->live()
                                ->afterStateUpdated(function ($set, $get, $state) use ($context) {
                                    if ($context && method_exists($context, 'recalculateItemTotal')) {
                                        $context->recalculateItemTotal($set, $get);
                                    }
                                }),

                            Forms\Components\TextInput::make('width')
                                ->label('Ancho (cm)')
                                ->numeric()
                                ->visible(function ($get) {
                                    $itemId = $get('itemable_id');
                                    if ($itemId) {
                                        $item = \App\Models\DigitalItem::find($itemId);
                                        return $item && $item->pricing_type === 'size';
                                    }
                                    return false;
                                })
                                ->required(function ($get) {
                                    $itemId = $get('itemable_id');
                                    if ($itemId) {
                                        $item = \App\Models\DigitalItem::find($itemId);
                                        return $item && $item->pricing_type === 'size';
                                    }
                                    return false;
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $get, $state) use ($context) {
                                    if ($context && method_exists($context, 'recalculateItemTotal')) {
                                        $context->recalculateItemTotal($set, $get);
                                    }
                                }),

                            Forms\Components\TextInput::make('height')
                                ->label('Alto (cm)')
                                ->numeric()
                                ->visible(function ($get) {
                                    $itemId = $get('itemable_id');
                                    if ($itemId) {
                                        $item = \App\Models\DigitalItem::find($itemId);
                                        return $item && $item->pricing_type === 'size';
                                    }
                                    return false;
                                })
                                ->required(function ($get) {
                                    $itemId = $get('itemable_id');
                                    if ($itemId) {
                                        $item = \App\Models\DigitalItem::find($itemId);
                                        return $item && $item->pricing_type === 'size';
                                    }
                                    return false;
                                })
                                ->live()
                                ->afterStateUpdated(function ($set, $get, $state) use ($context) {
                                    if ($context && method_exists($context, 'recalculateItemTotal')) {
                                        $context->recalculateItemTotal($set, $get);
                                    }
                                }),
                        ]),

                    // ARQUITECTURA 1: Acabados se manejan a nivel de itemable (DigitalItem)
                    // Los acabados se agregan desde DigitalItemQuickHandler, no desde DocumentItem
                    // Comentado para evitar conflicto con relationship 'finishings' que no existe en DocumentItem

                    // \Filament\Schemas\Components\Section::make('🎨 Acabados Opcionales')
                    //     ->description('Agrega acabados adicionales')
                    //     ->schema([
                    //         Forms\Components\Repeater::make('finishings')
                    //             ->label('')
                    //             ->relationship('finishings')
                    //             ->schema([
                    //                 \Filament\Schemas\Components\Grid::make(3)
                    //                     ->schema([
                    //                         Forms\Components\Select::make('finishing_id')
                    //                             ->label('Acabado')
                    //                             ->options(function () {
                    //                                 return \App\Models\Finishing::where('company_id', auth()->user()->company_id)
                    //                                     ->where('active', true)
                    //                                     ->pluck('name', 'id');
                    //                             })
                    //                             ->required()
                    //                             ->live(),
                    //
                    //                         Forms\Components\TextInput::make('quantity')
                    //                             ->label('Cantidad')
                    //                             ->numeric()
                    //                             ->default(1)
                    //                             ->required()
                    //                             ->live(),
                    //
                    //                         Forms\Components\TextInput::make('calculated_cost')
                    //                             ->label('Costo Calculado')
                    //                             ->numeric()
                    //                             ->prefix('$')
                    //                             ->disabled()
                    //                             ->dehydrated(),
                    //                     ])
                    //             ])
                    //             ->addActionLabel('Agregar Acabado')
                    //             ->collapsed()
                    //             ->columnSpanFull(),
                    //     ])
                    //     ->collapsible()
                    //     ->collapsed(),
                ]),
        ];
    }

    private static function createMagazineItemSchema($context): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\MagazineItem'),

            \Filament\Schemas\Components\Section::make('Revista')
                ->description('Configuración básica de revista')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título de la Revista')
                        ->required(),
                ])
        ];
    }

    private static function createTalonarioItemSchema($context): array
    {
        return [
            Forms\Components\Hidden::make('itemable_type')
                ->default('App\\Models\\TalonarioItem'),

            Group::make()
                ->schema(TalonarioItemForm::configure(new Schema)->getComponents())
                ->columnSpanFull(),
        ];
    }
}