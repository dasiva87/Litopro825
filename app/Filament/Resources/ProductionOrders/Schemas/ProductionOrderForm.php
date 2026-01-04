<?php

namespace App\Filament\Resources\ProductionOrders\Schemas;

use App\Enums\ProductionStatus;
use App\Models\Contact;
use App\Models\User;
use Filament\Forms\Components;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductionOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Orden de Producción')
                    ->description('Datos principales de la orden')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Components\TextInput::make('production_number')
                                    ->label('Número de Producción')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se genera automáticamente'),

                                Components\Select::make('status')
                                    ->label('Estado')
                                    ->options(ProductionStatus::class)
                                    ->required()
                                    ->default(ProductionStatus::DRAFT)
                                    ->native(false),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Components\Select::make('supplier_id')
                                    ->label('Proveedor')
                                    ->relationship(
                                        name: 'supplier',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query) {
                                            $currentCompanyId = auth()->user()->company_id ?? config('app.current_tenant_id');

                                            if (!$currentCompanyId) {
                                                return $query->whereRaw('1 = 0');
                                            }

                                            // Solo contactos de tipo proveedor de la empresa actual
                                            return $query->where('company_id', $currentCompanyId)
                                                ->whereIn('type', ['supplier', 'both']);
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (Contact $record) => "{$record->name}" . ($record->tax_id ? " - {$record->tax_id}" : ''))
                                    ->helperText('Selecciona un proveedor o crea uno nuevo')
                                    ->createOptionForm([
                                        Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required(),
                                        Components\Select::make('type')
                                            ->label('Tipo')
                                            ->options([
                                                'supplier' => 'Proveedor',
                                                'customer' => 'Cliente',
                                                'both' => 'Cliente y Proveedor',
                                            ])
                                            ->default('supplier')
                                            ->required(),
                                        Components\TextInput::make('contact_person')
                                            ->label('Persona de Contacto'),
                                        Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                        Components\TextInput::make('phone')
                                            ->label('Teléfono'),
                                        Components\TextInput::make('tax_id')
                                            ->label('NIT/RUT'),
                                        Components\Textarea::make('address')
                                            ->label('Dirección')
                                            ->rows(2),
                                        Components\Hidden::make('company_id')
                                            ->default(fn () => auth()->user()->company_id),
                                    ]),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Components\Select::make('operator_user_id')
                                    ->label('Operador Asignado')
                                    ->relationship(
                                        name: 'operator',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('company_id', auth()->user()->company_id)
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Usuario responsable de la producción'),
                            ]),
                    ]),

                Section::make('Programación')
                    ->description('Fechas y tiempos de producción')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Components\DatePicker::make('scheduled_date')
                                    ->label('Fecha Programada')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha planificada para producción'),

                                Components\DateTimePicker::make('started_at')
                                    ->label('Fecha de Inicio')
                                    ->native(false)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($get) => in_array($get('status'), [ProductionStatus::IN_PROGRESS, ProductionStatus::COMPLETED])),

                                Components\DateTimePicker::make('completed_at')
                                    ->label('Fecha de Finalización')
                                    ->native(false)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($get) => $get('status') === ProductionStatus::COMPLETED),
                            ]),
                    ]),

                Section::make('Métricas de Producción')
                    ->description('Datos calculados automáticamente')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Components\TextInput::make('total_items')
                                    ->label('Total Items')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('items')
                                    ->default(0),

                                Components\TextInput::make('total_impressions')
                                    ->label('Total Millares')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('millares')
                                    ->default(0),

                                Components\TextInput::make('estimated_hours')
                                    ->label('Horas Estimadas')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('horas')
                                    ->default(0),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Notas y Observaciones')
                    ->description('Información adicional')
                    ->schema([
                        Components\Textarea::make('notes')
                            ->label('Notas Generales')
                            ->placeholder('Observaciones sobre la orden de producción...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Components\Textarea::make('operator_notes')
                            ->label('Notas del Operador')
                            ->placeholder('Observaciones durante la producción...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array($get('status'), [ProductionStatus::IN_PROGRESS, ProductionStatus::COMPLETED])),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Control de Calidad')
                    ->description('Validación de calidad')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Components\Toggle::make('quality_checked')
                                    ->label('Calidad Verificada')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\Select::make('quality_checked_by')
                                    ->label('Verificado por')
                                    ->relationship('qualityCheckedBy', 'name')
                                    ->disabled()
                                    ->dehydrated(false),

                                Components\DateTimePicker::make('quality_checked_at')
                                    ->label('Fecha de Verificación')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($get) => $get('quality_checked') === true),
            ]);
    }
}
