<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Banner informativo para contactos Grafired
                ViewField::make('grafired_notice')
                    ->view('filament.components.grafired-contact-notice')
                    ->visible(fn ($record) => $record && !$record->is_local)
                    ->columnSpanFull(),

                // Grid principal de 2 columnas
                Grid::make(2)
                    ->schema([
                        // COLUMNA IZQUIERDA: Información de la Empresa + Notas
                        Grid::make(1)
                            ->schema([

                                // Tipo de contacto
                                Select::make('type')
                                    ->label('Tipo de Contacto')
                                    ->options([
                                        'customer' => 'Cliente',
                                        'supplier' => 'Proveedor',
                                        'both' => 'Cliente y Proveedor',
                                    ])
                                    ->default('customer')
                                    ->required()
                                    ->live()
                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                    ->dehydrated(fn ($record) => !$record || $record->is_local)
                                    ->columnSpanFull(),

                                Section::make('Información de la Empresa')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nombre o Razón Social')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        TextInput::make('tax_id')
                                            ->label('NIT/Cédula')
                                            ->maxLength(255)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Textarea::make('address')
                                            ->label('Dirección')
                                            ->rows(2)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Select::make('country_id')
                                            ->label('País')
                                            ->relationship(
                                                name: 'country',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('state_id', null);
                                                $set('city_id', null);
                                            })
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Select::make('state_id')
                                            ->label('Departamento/Estado')
                                            ->relationship(
                                                name: 'state',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query, $get) => $query
                                                    ->when(
                                                        $get('country_id'),
                                                        fn ($q, $countryId) => $q->where('country_id', $countryId)
                                                    )
                                                    ->where('is_active', true)
                                                    ->orderBy('name')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live(debounce: 500)
                                            ->disabled(fn ($get, $record) => !$get('country_id') || ($record && !$record->is_local))
                                            ->afterStateUpdated(fn (callable $set) => $set('city_id', null))
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Select::make('city_id')
                                            ->label('Ciudad')
                                            ->relationship(
                                                name: 'city',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query, $get) => $query
                                                    ->when(
                                                        $get('state_id'),
                                                        fn ($q, $stateId) => $q->where('state_id', $stateId)
                                                    )
                                                    ->where('is_active', true)
                                                    ->orderBy('name')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn ($get, $record) => !$get('state_id') || ($record && !$record->is_local))
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        TextInput::make('website')
                                            ->label('Sitio Web')
                                            ->url()
                                            ->maxLength(255)
                                            ->prefix('https://')
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),
                                    ]),

                                Section::make('Notas')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Observaciones')
                                            ->rows(4)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // COLUMNA DERECHA: Información de Contacto + Configuración Comercial
                        Grid::make(1)
                            ->schema([
                                Section::make('Información de Contacto')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        TextInput::make('contact_person')
                                            ->label('Contacto Principal')
                                            ->maxLength(255)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('Email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                                    ->dehydrated(fn ($record) => !$record || $record->is_local),

                                                TextInput::make('phone')
                                                    ->label('Teléfono')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                                    ->dehydrated(fn ($record) => !$record || $record->is_local),
                                            ]),

                                        TextInput::make('mobile')
                                            ->label('Móvil')
                                            ->tel()
                                            ->maxLength(255)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),

                                        Repeater::make('additional_contacts')
                                            ->label('Contactos Adicionales')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Nombre')
                                                            ->maxLength(255)
                                                            ->required(),

                                                        TextInput::make('email')
                                                            ->label('Email')
                                                            ->email()
                                                            ->maxLength(255),

                                                        TextInput::make('phone')
                                                            ->label('Teléfono')
                                                            ->tel()
                                                            ->maxLength(255),
                                                    ]),
                                            ])
                                            ->defaultItems(0)
                                            ->maxItems(5)
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nuevo contacto')
                                            ->addActionLabel('Agregar contacto')
                                            ->reorderable()
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),
                                    ]),

                                Section::make('Configuración Comercial')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('credit_limit')
                                                    ->label('Límite de Crédito')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(0)
                                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                                    ->dehydrated(fn ($record) => !$record || $record->is_local),

                                                TextInput::make('payment_terms')
                                                    ->label('Términos de Pago')
                                                    ->numeric()
                                                    ->suffix('días')
                                                    ->default(0)
                                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                                    ->dehydrated(fn ($record) => !$record || $record->is_local),

                                                TextInput::make('discount_percentage')
                                                    ->label('Descuento')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->maxValue(100)
                                                    ->default(0)
                                                    ->disabled(fn ($record) => $record && !$record->is_local)
                                                    ->dehydrated(fn ($record) => !$record || $record->is_local),
                                            ]),

                                        Toggle::make('is_active')
                                            ->label('Activo')
                                            ->default(true)
                                            ->disabled(fn ($record) => $record && !$record->is_local)
                                            ->dehydrated(fn ($record) => !$record || $record->is_local),
                                    ])
                                    ->visible(fn ($get) => in_array($get('type'), ['customer', 'both'])),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
