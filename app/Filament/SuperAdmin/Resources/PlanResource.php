<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Models\Plan;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Planes de Suscripción';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Planes';

    protected static UnitEnum|string|null $navigationGroup = 'Subscription Management';

    protected static ?int $navigationSort = 10;

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
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', \Str::slug($state))),

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
                            ->simple(
                                Forms\Components\TextInput::make('feature')
                                    ->label('Característica')
                                    ->required()
                                    ->placeholder('Ej: Hasta 10 usuarios')
                            )
                            ->columnSpanFull()
                            ->addActionLabel('Agregar característica')
                            ->helperText('Lista de características incluidas en este plan'),

                        Forms\Components\TextInput::make('trial_days')
                            ->label('Días de Prueba Gratuita')
                            ->numeric()
                            ->suffix('días')
                            ->default(0)
                            ->helperText('0 = sin período de prueba'),
                    ]),

                Section::make('Límites del Plan')
                    ->schema([
                        Forms\Components\Repeater::make('limits')
                            ->label('Límites y Restricciones')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Límite')
                                    ->required()
                                    ->placeholder('Ej: max_users'),

                                Forms\Components\TextInput::make('value')
                                    ->label('Valor')
                                    ->required()
                                    ->placeholder('Ej: 10'),

                                Forms\Components\TextInput::make('description')
                                    ->label('Descripción')
                                    ->placeholder('Ej: Máximo número de usuarios'),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => ($state['name'] ?? '') . ': ' . ($state['value'] ?? ''))
                            ->defaultItems(0)
                            ->addActionLabel('Agregar límite')
                            ->helperText('Límites técnicos del plan (usuarios, documentos, storage, etc.)'),
                    ]),

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

                Tables\Columns\TextColumn::make('trial_days')
                    ->label('Trial')
                    ->suffix(' días')
                    ->placeholder('Sin trial')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->counts('subscriptions')
                    ->badge()
                    ->color('info')
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
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\Pages\ListPlans::route('/'),
            'create' => \App\Filament\SuperAdmin\Resources\Pages\CreatePlan::route('/create'),
            'view' => \App\Filament\SuperAdmin\Resources\Pages\ViewPlan::route('/{record}'),
            'edit' => \App\Filament\SuperAdmin\Resources\Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}