<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        TextInput::make('email')
                            ->label('Email Corporativo')
                            ->email()
                            ->maxLength(255),
                            
                        TextInput::make('phone')
                            ->label('Teléfono Principal')
                            ->tel()
                            ->maxLength(255),
                            
                        TextInput::make('tax_id')
                            ->label('NIT/RUT')
                            ->maxLength(255),
                            
                        Textarea::make('address')
                            ->label('Dirección')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Ubicación')
                    ->schema([
                        Select::make('country_id')
                            ->label('País')
                            ->options(Country::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('state_id', null)),
                            
                        Select::make('state_id')
                            ->label('Departamento/Estado')
                            ->options(function (callable $get) {
                                $countryId = $get('country_id');
                                if (!$countryId) {
                                    return [];
                                }
                                return State::where('country_id', $countryId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('city_id', null)),
                            
                        Select::make('city_id')
                            ->label('Ciudad')
                            ->options(function (callable $get) {
                                $stateId = $get('state_id');
                                if (!$stateId) {
                                    return [];
                                }
                                return City::where('state_id', $stateId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Configuración de Suscripción')
                    ->schema([
                        Select::make('subscription_plan')
                            ->label('Plan de Suscripción')
                            ->options([
                                'free' => 'Gratuito',
                                'basic' => 'Básico',
                                'premium' => 'Premium',
                                'enterprise' => 'Empresarial',
                            ])
                            ->default('free')
                            ->required(),
                            
                        TextInput::make('max_users')
                            ->label('Máximo de Usuarios')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(1000),
                            
                        Toggle::make('is_active')
                            ->label('Empresa Activa')
                            ->default(true)
                            ->helperText('Las empresas inactivas no pueden acceder al sistema'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                    
                Section::make('Información Adicional')
                    ->schema([
                        TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}