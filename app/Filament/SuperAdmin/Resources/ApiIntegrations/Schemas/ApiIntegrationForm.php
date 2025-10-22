<?php

namespace App\Filament\SuperAdmin\Resources\ApiIntegrations\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiIntegrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('integration_type')
                            ->label('Tipo de Integración')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'testing' => 'Testing',
                                'suspended' => 'Suspendido',
                            ])
                            ->default('testing')
                            ->required(),
                    ])->columns(2),

                Section::make('Configuración de Autenticación')
                    ->schema([
                        Forms\Components\Select::make('auth_type')
                            ->label('Tipo de Autenticación')
                            ->options([
                                'none' => 'Ninguno',
                                'api_key' => 'API Key',
                                'bearer_token' => 'Bearer Token',
                                'oauth2' => 'OAuth2',
                                'basic_auth' => 'Basic Auth',
                                'signature' => 'Signature',
                            ])
                            ->default('api_key')
                            ->required(),

                        Forms\Components\TextInput::make('endpoint_url')
                            ->label('URL del Endpoint')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\Select::make('http_method')
                            ->label('Método HTTP')
                            ->options([
                                'GET' => 'GET',
                                'POST' => 'POST',
                                'PUT' => 'PUT',
                                'PATCH' => 'PATCH',
                                'DELETE' => 'DELETE',
                            ])
                            ->default('POST')
                            ->required(),
                    ])->columns(3),

                Section::make('Configuración Adicional')
                    ->schema([
                        Forms\Components\TextInput::make('timeout_seconds')
                            ->label('Timeout (segundos)')
                            ->numeric()
                            ->default(30)
                            ->required(),

                        Forms\Components\Toggle::make('verify_ssl')
                            ->label('Verificar SSL')
                            ->default(true),

                        Forms\Components\Toggle::make('log_requests')
                            ->label('Registrar Peticiones')
                            ->default(true),

                        Forms\Components\Toggle::make('log_responses')
                            ->label('Registrar Respuestas')
                            ->default(true),
                    ])->columns(2),

                Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ]),
            ]);
    }
}
