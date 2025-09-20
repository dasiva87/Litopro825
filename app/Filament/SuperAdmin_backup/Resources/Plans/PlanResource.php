<?php

namespace App\Filament\SuperAdmin\Resources\Plans;

use App\Enums\SuperAdminNavigationGroup;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Planes de Suscripción';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static ?SuperAdminNavigationGroup $navigationGroup = SuperAdminNavigationGroup::SubscriptionManagement;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Plan::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Precios y Facturación')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),

                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'COP' => 'COP - Peso Colombiano',
                                'USD' => 'USD - Dólar Americano',
                                'EUR' => 'EUR - Euro',
                            ])
                            ->default('COP')
                            ->required(),

                        Forms\Components\Select::make('interval')
                            ->label('Intervalo de Facturación')
                            ->options([
                                'month' => 'Mensual',
                                'year' => 'Anual',
                                'week' => 'Semanal',
                                'day' => 'Diario',
                            ])
                            ->default('month')
                            ->required(),

                        Forms\Components\TextInput::make('stripe_price_id')
                            ->label('PayU Plan ID')
                            ->helperText('Identificador único del plan para PayU (opcional)')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Características')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->label('Características')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Característica')
                                    ->required(),
                                Forms\Components\Toggle::make('included')
                                    ->label('Incluida')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),

                Section::make('Límites')
                    ->schema([
                        Forms\Components\Repeater::make('limits')
                            ->label('Límites del Plan')
                            ->schema([
                                Forms\Components\TextInput::make('feature')
                                    ->label('Funcionalidad')
                                    ->required()
                                    ->placeholder('ej: max_users, max_documents'),
                                Forms\Components\TextInput::make('limit')
                                    ->label('Límite')
                                    ->required()
                                    ->numeric()
                                    ->placeholder('ej: 10, 100, -1 para ilimitado'),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Unidad')
                                    ->placeholder('ej: usuarios, documentos'),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => isset($state['feature']) && isset($state['limit'])
                                    ? "{$state['feature']}: {$state['limit']}"
                                    : null
                            ),
                    ]),

                Section::make('Configuración PayU')
                    ->schema([
                        Forms\Components\Select::make('payment_methods')
                            ->label('Métodos de Pago Permitidos')
                            ->options([
                                'VISA' => 'Visa',
                                'MASTERCARD' => 'Mastercard',
                                'AMEX' => 'American Express',
                                'DINERS' => 'Diners Club',
                                'PSE' => 'PSE (Débito Online)',
                                'EFECTY' => 'Efecty',
                                'BALOTO' => 'Baloto',
                            ])
                            ->multiple()
                            ->default(['VISA', 'MASTERCARD', 'PSE'])
                            ->helperText('Selecciona los métodos de pago disponibles para este plan'),

                        Forms\Components\Textarea::make('payu_description')
                            ->label('Descripción para PayU')
                            ->helperText('Descripción que aparecerá en la pasarela de PayU')
                            ->rows(2)
                            ->maxLength(255),
                    ])->columns(1),

                Section::make('Configuración')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden de Visualización')
                            ->numeric()
                            ->default(0)
                            ->helperText('Menor número aparece primero'),
                    ])->columns(2),
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

                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money(fn (Plan $record): string => $record->currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('interval')
                    ->label('Facturación')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'month' => 'success',
                        'year' => 'info',
                        'week' => 'warning',
                        'day' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'month' => 'Mensual',
                        'year' => 'Anual',
                        'week' => 'Semanal',
                        'day' => 'Diario',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->counts('subscriptions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),

                Tables\Filters\SelectFilter::make('interval')
                    ->label('Facturación')
                    ->options([
                        'month' => 'Mensual',
                        'year' => 'Anual',
                        'week' => 'Semanal',
                        'day' => 'Diario',
                    ]),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'USD' => 'USD',
                        'COP' => 'COP',
                        'EUR' => 'EUR',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                Actions\Action::make('toggle_status')
                    ->label(fn (Plan $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (Plan $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Plan $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Plan $record) => $record->is_active ? 'Desactivar Plan' : 'Activar Plan')
                    ->modalDescription(fn (Plan $record) => $record->is_active
                        ? 'Las nuevas suscripciones no podrán usar este plan'
                        : 'El plan estará disponible para nuevas suscripciones'
                    )
                    ->action(function (Plan $record) {
                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'Plan activado' : 'Plan desactivado')
                            ->body("El plan {$record->name} ha sido ".($record->is_active ? 'activado' : 'desactivado'))
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('clone')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del nuevo plan')
                            ->required()
                            ->default(fn (Plan $record) => $record->name.' (Copia)'),
                    ])
                    ->action(function (Plan $record, array $data) {
                        $newPlan = $record->replicate();
                        $newPlan->name = $data['name'];
                        $newPlan->slug = \Str::slug($data['name']);
                        $newPlan->stripe_price_id = null; // Reset PayU ID
                        $newPlan->is_active = false; // Start as inactive
                        $newPlan->save();

                        Notification::make()
                            ->title('Plan duplicado')
                            ->body("Se ha creado el plan {$newPlan->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('bulk_activate')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }

                            Notification::make()
                                ->title('Planes activados')
                                ->body(count($records).' planes han sido activados')
                                ->success()
                                ->send();
                        }),

                    Actions\BulkAction::make('bulk_deactivate')
                        ->label('Desactivar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }

                            Notification::make()
                                ->title('Planes desactivados')
                                ->body(count($records).' planes han sido desactivados')
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // Relations can be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
