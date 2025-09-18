<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Company;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->required()
                    ->searchable(),

                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),

                TextInput::make('name')
                    ->label('Nombre de Suscripción')
                    ->default('default')
                    ->required(),

                TextInput::make('stripe_id')
                    ->label('PayU Transaction ID')
                    ->helperText('ID de transacción de PayU')
                    ->default(fn() => 'PAYU-' . now()->format('YmdHis') . '-' . rand(1000, 9999))
                    ->required(),

                Select::make('stripe_status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'cancelled' => 'Cancelada',
                        'incomplete' => 'Incompleta',
                        'trialing' => 'En Prueba',
                        'past_due' => 'Vencida',
                    ])
                    ->default('active')
                    ->required(),

                TextInput::make('stripe_price')
                    ->label('Plan')
                    ->helperText('Nombre del plan (basic, premium, enterprise)'),

                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->required(),

                DateTimePicker::make('trial_ends_at')
                    ->label('Fin de Prueba'),

                DateTimePicker::make('ends_at')
                    ->label('Fecha de Finalización'),
            ]);
    }
}
