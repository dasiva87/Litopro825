<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Company;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                            
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Información de Contacto')
                    ->schema([
                        Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'CC' => 'Cédula de Ciudadanía',
                                'NIT' => 'NIT',
                                'CE' => 'Cédula de Extranjería',
                                'passport' => 'Pasaporte',
                            ])
                            ->default('CC'),
                            
                        TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->maxLength(255),
                            
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                            
                        TextInput::make('mobile')
                            ->label('Celular')
                            ->tel()
                            ->maxLength(255),
                            
                        TextInput::make('position')
                            ->label('Cargo/Posición')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Configuración')
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->options(Company::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                            
                        Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->options(Role::all()->pluck('name', 'name'))
                            ->searchable(),
                            
                        Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}