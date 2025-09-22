<?php

namespace App\Filament\SuperAdmin\Resources\NotificationChannels;

use App\Filament\SuperAdmin\Resources\NotificationChannels\Pages\CreateNotificationChannel;
use App\Filament\SuperAdmin\Resources\NotificationChannels\Pages\EditNotificationChannel;
use App\Filament\SuperAdmin\Resources\NotificationChannels\Pages\ListNotificationChannels;
use App\Filament\SuperAdmin\Resources\NotificationChannels\Schemas\NotificationChannelForm;
use App\Filament\SuperAdmin\Resources\NotificationChannels\Tables\NotificationChannelsTable;
use App\Models\NotificationChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NotificationChannelResource extends Resource
{
    protected static ?string $model = NotificationChannel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Canales de Notificación';

    protected static ?string $modelLabel = 'Canal de Notificación';

    protected static ?string $pluralModelLabel = 'Canales de Notificación';

    protected static UnitEnum|string|null $navigationGroup = 'Enterprise Features';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return NotificationChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationChannelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationChannels::route('/'),
            'create' => CreateNotificationChannel::route('/create'),
            'edit' => EditNotificationChannel::route('/{record}/edit'),
        ];
    }
}
