<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'customer' => 'Cliente',
                                        'supplier' => 'Proveedor', 
                                        'both' => 'Cliente y Proveedor',
                                    ])
                                    ->default('customer')
                                    ->required()
                                    ->live(),
                                    
                                TextInput::make('name')
                                    ->label('Nombre o Razón Social')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TextInput::make('contact_person')
                                    ->label('Persona de Contacto')
                                    ->maxLength(255),
                                    
                                TextInput::make('tax_id')
                                    ->label('NIT/Cédula')
                                    ->maxLength(255),
                            ]),
                    ]),
                    
                Section::make('Información de Contacto')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                    
                                TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(255),
                                    
                                TextInput::make('mobile')
                                    ->label('Móvil')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                            
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3),
                            
                        Grid::make(3)
                            ->schema([
                                Select::make('country_id')
                                    ->label('País')
                                    ->relationship('country', 'name')
                                    ->searchable()
                                    ->live(),
                                    
                                Select::make('state_id')
                                    ->label('Departamento/Estado')
                                    ->relationship('state', 'name')
                                    ->searchable()
                                    ->live(),
                                    
                                Select::make('city_id')
                                    ->label('Ciudad')
                                    ->relationship('city', 'name')
                                    ->searchable(),
                            ]),
                            
                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(255),
                    ]),
                    
                Section::make('Configuración Comercial')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('credit_limit')
                                    ->label('Límite de Crédito')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                    
                                TextInput::make('payment_terms')
                                    ->label('Términos de Pago (días)')
                                    ->numeric()
                                    ->suffix('días')
                                    ->default(0),
                                    
                                TextInput::make('discount_percentage')
                                    ->label('Descuento (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->maxValue(100)
                                    ->default(0),
                            ]),
                    ])
                    ->visible(fn ($get) => in_array($get('type'), ['customer', 'both'])),
                    
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(4),
                    ]),
                    
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
