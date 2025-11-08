<?php

namespace App\Filament\Resources\CollectionAccounts\Schemas;

use App\Enums\CollectionAccountStatus;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollectionAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Cuenta')
                    ->description('Datos principales de la cuenta de cobro')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Components\TextInput::make('account_number')
                                    ->label('Número de Cuenta')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\Select::make('status')
                                    ->label('Estado')
                                    ->options(fn () => collect(CollectionAccountStatus::cases())
                                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                                    )
                                    ->required()
                                    ->default(CollectionAccountStatus::DRAFT)
                                    ->native(false),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Components\Select::make('client_type')
                                    ->label('Tipo de Cliente')
                                    ->options([
                                        'company' => 'Empresa Conectada',
                                        'contact' => 'Cliente/Proveedor',
                                    ])
                                    ->default('contact')
                                    ->required()
                                    ->live()
                                    ->helperText('Selecciona si el cliente es una empresa del sistema o un contacto externo'),

                                Components\Select::make('client_company_id')
                                    ->label('Empresa Cliente')
                                    ->relationship(
                                        name: 'clientCompany',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query) {
                                            $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

                                            if (!$currentCompanyId) {
                                                return $query->whereRaw('1 = 0');
                                            }

                                            // Obtener IDs de empresas conectadas como clientes aprobados
                                            $clientCompanyIds = \App\Models\CompanyConnection::where('company_id', $currentCompanyId)
                                                ->where('connection_type', \App\Models\CompanyConnection::TYPE_CLIENT)
                                                ->where('status', \App\Models\CompanyConnection::STATUS_APPROVED)
                                                ->pluck('connected_company_id');

                                            return $query->whereIn('id', $clientCompanyIds);
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($get) => $get('client_type') === 'company')
                                    ->required(fn ($get) => $get('client_type') === 'company')
                                    ->helperText('Empresas conectadas como clientes aprobados')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('contact_id', null) : null),

                                Components\Select::make('contact_id')
                                    ->label('Cliente/Contacto')
                                    ->relationship(
                                        name: 'contact',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query) {
                                            $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

                                            if (!$currentCompanyId) {
                                                return $query->whereRaw('1 = 0');
                                            }

                                            // Solo contactos de tipo cliente de la empresa actual
                                            return $query->where('company_id', $currentCompanyId)
                                                ->whereIn('type', ['customer', 'both']);
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($get) => $get('client_type') === 'contact')
                                    ->required(fn ($get) => $get('client_type') === 'contact')
                                    ->helperText('Clientes y contactos registrados en el sistema')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('client_company_id', null) : null),

                                Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Section::make('Fechas')
                    ->description('Fechas importantes de la cuenta')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Components\DatePicker::make('issue_date')
                                    ->label('Fecha de Emisión')
                                    ->required()
                                    ->default(now()),

                                Components\DatePicker::make('due_date')
                                    ->label('Fecha de Vencimiento')
                                    ->after('issue_date'),

                                Components\DatePicker::make('paid_date')
                                    ->label('Fecha de Pago')
                                    ->after('issue_date')
                                    ->visible(fn ($get) => $get('status') === 'paid'),
                            ]),
                    ]),

                Section::make('Información Adicional')
                    ->description('Notas y observaciones')
                    ->schema([
                        Components\Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Notas adicionales sobre la cuenta de cobro...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Metadatos')
                    ->description('Información de auditoría')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Components\Select::make('created_by')
                                    ->label('Creado por')
                                    ->relationship('createdBy', 'name')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\Select::make('approved_by')
                                    ->label('Aprobado por')
                                    ->relationship('approvedBy', 'name')
                                    ->visible(fn ($get) => in_array($get('status'), ['approved', 'paid'])),
                            ]),
                    ])
                    ->visibleOn('edit'),
            ]);
    }
}
