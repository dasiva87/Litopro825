<?php

namespace App\Filament\SuperAdmin\Resources\AutomatedReports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AutomatedReportForm
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
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'])
                    ->default('draft')
                    ->required(),
                TextInput::make('report_type')
                    ->required(),
                Textarea::make('data_sources')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('metrics')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('filters')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('grouping')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('frequency')
                    ->options([
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'custom' => 'Custom',
        ])
                    ->default('monthly')
                    ->required(),
                TextInput::make('custom_cron')
                    ->default(null),
                TextInput::make('day_of_month')
                    ->numeric()
                    ->default(null),
                TextInput::make('day_of_week')
                    ->numeric()
                    ->default(null),
                TimePicker::make('time_of_day')
                    ->required(),
                TextInput::make('timezone')
                    ->required()
                    ->default('UTC'),
                Textarea::make('recipients')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('delivery_methods')
                    ->required()
                    ->columnSpanFull(),
                Select::make('format')
                    ->options(['pdf' => 'Pdf', 'excel' => 'Excel', 'csv' => 'Csv', 'html' => 'Html', 'json' => 'Json'])
                    ->default('pdf')
                    ->required(),
                Toggle::make('include_charts')
                    ->required(),
                Toggle::make('include_raw_data')
                    ->required(),
                TextInput::make('template')
                    ->default(null),
                Textarea::make('chart_configs')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('custom_message')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('branding')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('retention_days')
                    ->required()
                    ->numeric()
                    ->default(90),
                Toggle::make('archive_reports')
                    ->required(),
                Textarea::make('alert_conditions')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('alert_thresholds')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('send_only_on_changes')
                    ->required(),
                DateTimePicker::make('last_run_at'),
                DateTimePicker::make('next_run_at'),
                TextInput::make('execution_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('last_error')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('last_status')
                    ->options(['success' => 'Success', 'failed' => 'Failed', 'partial' => 'Partial'])
                    ->default(null),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
