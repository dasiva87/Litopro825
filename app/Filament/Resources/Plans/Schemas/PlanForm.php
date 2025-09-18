<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\KeyValue;
use Illuminate\Support\Str;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->description('Información principal del plan de suscripción')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Plan')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Configuración de Stripe')
                    ->description('Configuración de precios y facturación')
                    ->schema([
                        TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('ID del precio en Stripe (ej: price_1234567890)'),

                        TextInput::make('price')
                            ->label('Precio')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),

                        Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'usd' => 'USD - Dólar Americano',
                                'eur' => 'EUR - Euro',
                                'cop' => 'COP - Peso Colombiano',
                            ])
                            ->default('usd')
                            ->required(),

                        Select::make('interval')
                            ->label('Intervalo de Facturación')
                            ->options([
                                'month' => 'Mensual',
                                'year' => 'Anual',
                            ])
                            ->default('month')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Características y Límites')
                    ->description('Define las características y límites del plan')
                    ->schema([
                        TagsInput::make('features')
                            ->label('Características')
                            ->helperText('Lista de características incluidas en el plan')
                            ->placeholder('Agregar característica')
                            ->columnSpanFull(),

                        KeyValue::make('limits')
                            ->label('Límites del Plan')
                            ->helperText('Define los límites para este plan (ej: max_users: 10, max_documents: 100)')
                            ->keyLabel('Límite')
                            ->valueLabel('Valor')
                            ->columnSpanFull(),
                    ]),

                Section::make('Configuración General')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Plan Activo')
                            ->helperText('Los planes inactivos no estarán disponibles para nuevas suscripciones')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Orden de Visualización')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden en que aparece el plan (menor número = primero)'),
                    ])
                    ->columns(2),
            ]);
    }
}
