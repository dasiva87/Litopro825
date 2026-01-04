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
                                        ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
                                    )
                                    ->required()
                                    ->default(CollectionAccountStatus::DRAFT)
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Components\Select::make('contact_id')
                                    ->label('Cliente')
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
                                    ->required()
                                    ->helperText('Selecciona un cliente o crea uno nuevo')
                                    ->createOptionForm([
                                        Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        Components\Select::make('type')
                                            ->label('Tipo')
                                            ->options([
                                                'customer' => 'Cliente',
                                                'supplier' => 'Proveedor',
                                                'both' => 'Cliente y Proveedor',
                                            ])
                                            ->default('customer')
                                            ->required(),
                                        Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                        Components\TextInput::make('phone')
                                            ->label('Teléfono'),
                                    ]),

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
