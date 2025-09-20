<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('document_type')
                    ->required()
                    ->default('CC'),
                TextInput::make('document_number')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('mobile')
                    ->default(null),
                TextInput::make('position')
                    ->default(null),
                Textarea::make('address')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->default(null),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->default(null),
                Select::make('state_id')
                    ->relationship('state', 'name')
                    ->default(null),
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->default(null),
                TextInput::make('avatar')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_login_at'),
                TextInput::make('stripe_id')
                    ->default(null),
                TextInput::make('pm_type')
                    ->default(null),
                TextInput::make('pm_last_four')
                    ->default(null),
                DateTimePicker::make('trial_ends_at'),
            ]);
    }
}
