<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogResource\Pages;

use App\Filament\SuperAdmin\Resources\ActivityLogResource;
use Filament\Schemas\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Log ID'),

                        TextEntry::make('event')
                            ->label('Event Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'login' => 'success',
                                'logout' => 'warning',
                                'created' => 'info',
                                'updated' => 'primary',
                                'deleted' => 'danger',
                                'failed_login' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('description')
                            ->label('Description'),

                        TextEntry::make('created_at')
                            ->label('Timestamp')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('User Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('User Name')
                            ->placeholder('System/Unknown'),

                        TextEntry::make('user.email')
                            ->label('User Email')
                            ->placeholder('N/A'),

                        TextEntry::make('company.name')
                            ->label('Company')
                            ->placeholder('N/A'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('N/A'),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Subject Information')
                    ->schema([
                        TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'N/A'
                            ),

                        TextEntry::make('subject_id')
                            ->label('Subject ID')
                            ->placeholder('N/A'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->subject_type || $record->subject_id),

                Section::make('Additional Properties')
                    ->schema([
                        KeyValueEntry::make('properties')
                            ->label('Properties')
                            ->keyLabel('Property')
                            ->valueLabel('Value'),
                    ])
                    ->visible(fn ($record) => ! empty($record->properties)),
            ]);
    }
}
