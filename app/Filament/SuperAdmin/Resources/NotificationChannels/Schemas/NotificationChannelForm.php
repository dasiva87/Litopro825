<?php

namespace App\Filament\SuperAdmin\Resources\NotificationChannels\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NotificationChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('type')
                    ->options([
            'email' => 'Email',
            'slack' => 'Slack',
            'teams' => 'Teams',
            'discord' => 'Discord',
            'webhook' => 'Webhook',
            'sms' => 'Sms',
            'push' => 'Push',
            'database' => 'Database',
        ])
                    ->default('email')
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'testing' => 'Testing'])
                    ->default('active')
                    ->required(),
                Textarea::make('config')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('rate_limits')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('retry_settings')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('default_template')
                    ->default(null),
                Textarea::make('format_settings')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('filters')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('business_hours')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('allowed_event_types')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('supports_realtime')
                    ->required(),
                Toggle::make('supports_bulk')
                    ->required(),
                TextInput::make('total_sent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_delivered')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_failed')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('last_used_at'),
                Textarea::make('last_error')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
