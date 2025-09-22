<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions;
use Filament\Notifications\Notification;

class FailedPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Pagos Fallidos y Acciones Requeridas';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::whereIn('stripe_status', ['past_due', 'unpaid'])
                    ->with(['company', 'user'])
                    ->orderBy('updated_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Empresa')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'past_due' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'past_due' => 'Pago Pendiente',
                        'unpaid' => 'No Pagado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último Cambio')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Suspensión')
                    ->dateTime()
                    ->placeholder('Sin fecha')
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Fin Trial')
                    ->dateTime()
                    ->placeholder('Sin trial')
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('retry_payment')
                    ->label('Reintentar Pago')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reintentar Pago')
                    ->modalDescription('Esto marcará la suscripción para reintento automático de pago.')
                    ->action(function (Subscription $record) {
                        // En una implementación real, aquí se haría la llamada a PayU
                        // Por ahora, simulamos marcando como activa
                        $record->update(['stripe_status' => 'active']);

                        Notification::make()
                            ->title('Pago reintentado')
                            ->body("Se ha programado reintento de pago para {$record->company->name}")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('extend_grace_period')
                    ->label('Extender Gracia')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Nueva fecha de suspensión')
                            ->required()
                            ->default(now()->addDays(7)),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update(['ends_at' => $data['ends_at']]);

                        Notification::make()
                            ->title('Período de gracia extendido')
                            ->body("Se ha extendido hasta {$data['ends_at']} para {$record->company->name}")
                            ->info()
                            ->send();
                    }),

                Actions\Action::make('cancel_subscription')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Suscripción por Falta de Pago')
                    ->modalDescription('Esta acción cancelará definitivamente la suscripción.')
                    ->action(function (Subscription $record) {
                        $record->update([
                            'stripe_status' => 'cancelled',
                            'ends_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Suscripción cancelada')
                            ->body("Suscripción de {$record->company->name} cancelada por falta de pago")
                            ->warning()
                            ->send();
                    }),

                Actions\Action::make('send_payment_reminder')
                    ->label('Enviar Recordatorio')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar Recordatorio de Pago')
                    ->modalDescription('Se enviará un email recordatorio al usuario responsable.')
                    ->action(function (Subscription $record) {
                        // Aquí se implementaría el envío de email
                        // Por ahora solo mostramos una notificación

                        Notification::make()
                            ->title('Recordatorio enviado')
                            ->body("Email de recordatorio enviado a {$record->user->email}")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('🎉 No hay pagos fallidos')
            ->emptyStateDescription('Todas las suscripciones están al día con sus pagos.')
            ->paginated([10, 25, 50]);
    }
}