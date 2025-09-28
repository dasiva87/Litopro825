<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                            ->unique(table: 'users', column: 'email', ignorable: fn($record) => $record)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->maxLength(255),

                        TextInput::make('position')
                            ->label('Cargo/Posición')
                            ->maxLength(100),
                    ])->columns(2),

                Section::make('Información de Contacto')
                    ->schema([
                        TextInput::make('document_type')
                            ->label('Tipo de Documento')
                            ->default('CC')
                            ->maxLength(10),

                        TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->maxLength(20),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('mobile')
                            ->label('Celular')
                            ->maxLength(20),
                    ])->columns(2),

                Section::make('Roles y Permisos')
                    ->schema([
                        Select::make('role')
                            ->label('Rol')
                            ->options(function () {
                                // Solo mostrar roles que puede asignar el company admin
                                $availableRoles = ['Manager', 'Salesperson', 'Operator', 'Customer'];

                                // Si es Super Admin, puede asignar todos los roles
                                if (auth()->user()->hasRole('Super Admin')) {
                                    $availableRoles = ['Super Admin', 'Company Admin', 'Manager', 'Salesperson', 'Operator', 'Customer'];
                                }
                                // Si es Company Admin, puede asignar todos excepto Super Admin
                                elseif (auth()->user()->hasRole('Company Admin')) {
                                    $availableRoles = ['Company Admin', 'Manager', 'Salesperson', 'Operator', 'Customer'];
                                }

                                return Role::whereIn('name', $availableRoles)
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->required()
                            ->visible(fn() => auth()->user()->can('assignRoles', auth()->user())),
                    ])->columns(1),

                Section::make('Estado')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->default(true),
                    ])->columns(1),
            ]);
    }
}
