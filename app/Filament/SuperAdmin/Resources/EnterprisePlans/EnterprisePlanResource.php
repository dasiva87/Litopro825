<?php

namespace App\Filament\SuperAdmin\Resources\EnterprisePlans;

use App\Filament\SuperAdmin\Resources\EnterprisePlans\Pages\CreateEnterprisePlan;
use App\Filament\SuperAdmin\Resources\EnterprisePlans\Pages\EditEnterprisePlan;
use App\Filament\SuperAdmin\Resources\EnterprisePlans\Pages\ListEnterprisePlans;
use App\Models\EnterprisePlan;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EnterprisePlanResource extends Resource
{
    protected static ?string $model = EnterprisePlan::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Enterprise Plans';

    protected static ?string $modelLabel = 'Plan Enterprise';

    protected static ?string $pluralModelLabel = 'Planes Enterprise';

    protected static UnitEnum|string|null $navigationGroup = 'Enterprise Features';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Plan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Plan Enterprise para [Cliente]'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción detallada del plan personalizado')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Section::make('Cliente y Plan Base')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Cliente Enterprise')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Cliente para el cual se crea este plan personalizado'),

                        Forms\Components\Select::make('base_plan_id')
                            ->label('Plan Base')
                            ->relationship('basePlan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Plan del cual se deriva este plan enterprise'),

                        Forms\Components\Select::make('sales_rep_id')
                            ->label('Representante de Ventas')
                            ->relationship('salesRep', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Usuario responsable de esta cuenta'),
                    ])->columns(3),

                Section::make('Configuración de Precios')
                    ->schema([
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Precio Personalizado')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Dejar vacío para usar precio del plan base'),

                        Forms\Components\Select::make('custom_interval')
                            ->label('Ciclo de Facturación')
                            ->options([
                                'month' => 'Mensual',
                                'year' => 'Anual',
                                'quarter' => 'Trimestral',
                                'custom' => 'Personalizado',
                            ])
                            ->helperText('Dejar vacío para usar ciclo del plan base'),

                        Forms\Components\TextInput::make('custom_interval_count')
                            ->label('Frecuencia')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Cada X períodos'),

                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Descuento Adicional (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1),
                    ])->columns(2),

                Section::make('Features y Límites Personalizados')
                    ->schema([
                        Forms\Components\Repeater::make('additional_features')
                            ->label('Features Adicionales')
                            ->simple(
                                Forms\Components\TextInput::make('feature')
                                    ->label('Feature')
                                    ->required()
                            )
                            ->helperText('Features exclusivas para este cliente'),

                        Forms\Components\Repeater::make('removed_features')
                            ->label('Features Excluidas')
                            ->simple(
                                Forms\Components\TextInput::make('feature')
                                    ->label('Feature')
                                    ->required()
                            )
                            ->helperText('Features del plan base que no aplican'),

                        Forms\Components\Textarea::make('custom_limits')
                            ->label('Límites Personalizados (JSON)')
                            ->helperText('Override de límites del plan base (ej: {"users": 1000})')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Configuración de Facturación')
                    ->schema([
                        Forms\Components\Toggle::make('custom_billing_cycle')
                            ->label('Ciclo de Facturación Personalizado'),

                        Forms\Components\TextInput::make('billing_day')
                            ->label('Día de Facturación')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->helperText('Día específico del mes para facturar'),

                        Forms\Components\Select::make('payment_terms')
                            ->label('Términos de Pago')
                            ->options([
                                'immediate' => 'Inmediato',
                                'net15' => 'NET 15',
                                'net30' => 'NET 30',
                                'net45' => 'NET 45',
                                'net60' => 'NET 60',
                                'net90' => 'NET 90',
                            ]),

                        Forms\Components\Toggle::make('requires_po')
                            ->label('Requiere Purchase Order'),

                        Forms\Components\Textarea::make('billing_notes')
                            ->label('Notas de Facturación')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('SLA y Soporte')
                    ->schema([
                        Forms\Components\Select::make('support_tier')
                            ->label('Nivel de Soporte')
                            ->options([
                                'basic' => 'Básico',
                                'premium' => 'Premium',
                                'enterprise' => 'Enterprise',
                                'white-glove' => 'White Glove',
                            ]),

                        Forms\Components\Toggle::make('dedicated_support')
                            ->label('Soporte Dedicado'),

                        Forms\Components\TextInput::make('account_manager_email')
                            ->label('Email Account Manager')
                            ->email(),

                        Forms\Components\Textarea::make('sla_terms')
                            ->label('Términos de SLA (JSON)')
                            ->helperText('Términos específicos del acuerdo de nivel de servicio')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Configuración Técnica')
                    ->schema([
                        Forms\Components\TextInput::make('api_rate_limit')
                            ->label('Límite de API Calls')
                            ->numeric()
                            ->helperText('Calls por minuto'),

                        Forms\Components\Toggle::make('white_labeling')
                            ->label('White Labeling'),

                        Forms\Components\Toggle::make('single_sign_on')
                            ->label('Single Sign-On (SSO)'),

                        Forms\Components\Textarea::make('custom_integrations')
                            ->label('Integraciones Personalizadas (JSON)')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('security_requirements')
                            ->label('Requerimientos de Seguridad (JSON)')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Período del Contrato')
                    ->schema([
                        Forms\Components\DateTimePicker::make('effective_date')
                            ->label('Fecha de Inicio')
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('expiration_date')
                            ->label('Fecha de Expiración')
                            ->helperText('Dejar vacío para contrato indefinido'),

                        Forms\Components\TextInput::make('contract_length_months')
                            ->label('Duración del Contrato (meses)')
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\Toggle::make('auto_renewal')
                            ->label('Renovación Automática'),
                    ])->columns(2),

                Section::make('Notas Internas')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(3)
                            ->helperText('Información para uso interno del equipo'),

                        Forms\Components\Repeater::make('contract_documents')
                            ->label('Documentos del Contrato')
                            ->simple(
                                Forms\Components\TextInput::make('url')
                                    ->label('URL del Documento')
                                    ->url()
                                    ->required()
                            )
                            ->helperText('URLs de documentos relacionados al contrato'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('basePlan.name')
                    ->label('Plan Base')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Aprobación')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('getEffectivePrice')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Vigencia')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('salesRep.name')
                    ->label('Sales Rep')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ]),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Estado de Aprobación')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),

                Tables\Filters\Filter::make('needs_renewal')
                    ->label('Necesita Renovación')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('expiration_date', '<=', now()->addDays(30))
                              ->where('auto_renewal', false)
                    ),
            ])
            ->actions([
                EditAction::make(),

                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EnterprisePlan $record) => $record->canBeApproved())
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Notas de Aprobación')
                            ->required(),
                    ])
                    ->action(function (EnterprisePlan $record, array $data) {
                        $record->approve(auth()->user(), $data['approval_notes']);

                        Notification::make()
                            ->title('Plan Enterprise Aprobado')
                            ->body("El plan '{$record->name}' ha sido aprobado exitosamente")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EnterprisePlan $record) => $record->approval_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_notes')
                            ->label('Motivo del Rechazo')
                            ->required(),
                    ])
                    ->action(function (EnterprisePlan $record, array $data) {
                        $record->reject(auth()->user(), $data['rejection_notes']);

                        Notification::make()
                            ->title('Plan Enterprise Rechazado')
                            ->body("El plan '{$record->name}' ha sido rechazado")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnterprisePlans::route('/'),
            'create' => CreateEnterprisePlan::route('/create'),
            'edit' => EditEnterprisePlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', 'pending')->count();
    }
}
