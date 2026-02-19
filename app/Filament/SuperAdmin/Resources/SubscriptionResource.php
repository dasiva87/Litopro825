<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\Subscriptions\Schemas\SubscriptionInfolist;
use App\Models\Subscription;
use App\Models\Company;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Suscripciones';

    protected static ?string $modelLabel = 'Suscripción';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    protected static UnitEnum|string|null $navigationGroup = 'Subscription Management';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Suscripción')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Usuario Responsable')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Suscripción')
                            ->required()
                            ->default('default')
                            ->maxLength(255),
                    ])->columns(3),

                Section::make('Plan y Estado')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_price')
                            ->label('Plan/Precio ID')
                            ->helperText('ID del plan en PayU o referencia del precio')
                            ->maxLength(255),

                        Forms\Components\Select::make('stripe_status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'cancelled' => 'Cancelado',
                                'past_due' => 'Pago Pendiente',
                                'unpaid' => 'No Pagado',
                                'trialing' => 'En Prueba',
                                'incomplete' => 'Incompleto',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])->columns(3),

                Section::make('Configuración de Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Fin del Período de Prueba')
                            ->helperText('Opcional: fecha de finalización del período de prueba'),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Fecha de Finalización')
                            ->helperText('Opcional: fecha de cancelación o finalización'),
                    ])->columns(2),

                Section::make('Información PayU')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_id')
                            ->label('PayU Transaction ID')
                            ->helperText('ID de transacción en PayU para esta suscripción')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Suscripción')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'cancelled' => 'danger',
                        'past_due' => 'warning',
                        'unpaid' => 'danger',
                        'incomplete' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'trialing' => 'En Prueba',
                        'cancelled' => 'Cancelado',
                        'past_due' => 'Pago Pendiente',
                        'unpaid' => 'No Pagado',
                        'incomplete' => 'Incompleto',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Fin Prueba')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Finaliza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'trialing' => 'En Prueba',
                        'cancelled' => 'Cancelado',
                        'past_due' => 'Pago Pendiente',
                        'unpaid' => 'No Pagado',
                        'incomplete' => 'Incompleto',
                    ]),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('active_subscriptions')
                    ->label('Solo Activas')
                    ->query(fn ($query) => $query->where('stripe_status', 'active')),

                Tables\Filters\Filter::make('trial_ending_soon')
                    ->label('Prueba Termina Pronto')
                    ->query(fn ($query) => $query->where('trial_ends_at', '>=', now())
                        ->where('trial_ends_at', '<=', now()->addDays(7))),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                Actions\Action::make('cancel_subscription')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Suscripción')
                    ->modalDescription('¿Estás seguro de que quieres cancelar esta suscripción? Esta acción no se puede deshacer.')
                    ->visible(fn (Subscription $record) => $record->stripe_status === 'active')
                    ->action(function (Subscription $record) {
                        $record->update([
                            'stripe_status' => 'cancelled',
                            'ends_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Suscripción cancelada')
                            ->body("La suscripción de {$record->company->name} ha sido cancelada")
                            ->warning()
                            ->send();
                    }),

                Actions\Action::make('reactivate_subscription')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar Suscripción')
                    ->modalDescription('¿Quieres reactivar esta suscripción?')
                    ->visible(fn (Subscription $record) => $record->stripe_status === 'cancelled')
                    ->action(function (Subscription $record) {
                        $record->update([
                            'stripe_status' => 'active',
                            'ends_at' => null,
                        ]);

                        Notification::make()
                            ->title('Suscripción reactivada')
                            ->body("La suscripción de {$record->company->name} ha sido reactivada")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('extend_trial')
                    ->label('Extender Prueba')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Nueva fecha de fin de prueba')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update([
                            'trial_ends_at' => $data['trial_ends_at'],
                            'stripe_status' => 'trialing',
                        ]);

                        Notification::make()
                            ->title('Prueba extendida')
                            ->body("Se ha extendido el período de prueba hasta {$data['trial_ends_at']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),

                    Actions\BulkAction::make('bulk_cancel')
                        ->label('Cancelar Seleccionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'stripe_status' => 'cancelled',
                                    'ends_at' => now(),
                                ]);
                            }

                            Notification::make()
                                ->title('Suscripciones canceladas')
                                ->body(count($records).' suscripciones han sido canceladas')
                                ->warning()
                                ->send();
                        }),

                    Actions\BulkAction::make('bulk_reactivate')
                        ->label('Reactivar Seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'stripe_status' => 'active',
                                    'ends_at' => null,
                                ]);
                            }

                            Notification::make()
                                ->title('Suscripciones reactivadas')
                                ->body(count($records).' suscripciones han sido reactivadas')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\Pages\ListSubscriptions::route('/'),
            'create' => \App\Filament\SuperAdmin\Resources\Pages\CreateSubscription::route('/create'),
            'view' => \App\Filament\SuperAdmin\Resources\Pages\ViewSubscription::route('/{record}'),
            'edit' => \App\Filament\SuperAdmin\Resources\Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('stripe_status', 'active')->count();
    }
}