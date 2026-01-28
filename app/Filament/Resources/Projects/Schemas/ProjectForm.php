<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Proyecto')
                    ->description('Datos principales del proyecto')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre del Proyecto')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Campaña Publicitaria Q1 2026'),

                                TextInput::make('code')
                                    ->label('Código')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Se genera automáticamente'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Estado')
                                    ->options(ProjectStatus::class)
                                    ->required()
                                    ->default(ProjectStatus::DRAFT)
                                    ->native(false),

                                Select::make('contact_id')
                                    ->label('Cliente')
                                    ->relationship(
                                        name: 'contact',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query
                                            ->forCurrentTenant()
                                            ->whereIn('type', ['customer', 'both'])
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción general del proyecto...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Fechas y Presupuesto')
                    ->description('Planificación del proyecto')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->default(now()),

                                DatePicker::make('estimated_end_date')
                                    ->label('Fecha Estimada de Fin')
                                    ->after('start_date'),

                                TextInput::make('budget')
                                    ->label('Presupuesto')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                            ]),
                    ]),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas Internas')
                            ->rows(4)
                            ->placeholder('Notas adicionales sobre el proyecto...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
