<?php

namespace App\Filament\SuperAdmin\Resources\PlanExperiments;

use App\Models\Plan;
use App\Models\PlanExperiment;
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

class PlanExperimentResource extends Resource
{
    protected static ?string $model = PlanExperiment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'A/B Testing';

    protected static ?string $modelLabel = 'Experimento';

    protected static ?string $pluralModelLabel = 'Experimentos A/B';

    protected static UnitEnum|string|null $navigationGroup = 'Enterprise Features';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuración del Experimento')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Experimento')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Test Precio Plan Básico'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Describe el objetivo y hipótesis del experimento')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Planes a Comparar')
                    ->schema([
                        Forms\Components\Select::make('control_plan_id')
                            ->label('Plan Control (Original)')
                            ->relationship('controlPlan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Plan actual que se está usando'),

                        Forms\Components\Select::make('variant_plan_id')
                            ->label('Plan Variante (Nuevo)')
                            ->relationship('variantPlan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Plan nuevo que quieres probar'),
                    ])->columns(2),

                Section::make('Configuración Estadística')
                    ->schema([
                        Forms\Components\TextInput::make('traffic_split')
                            ->label('División de Tráfico (%)')
                            ->numeric()
                            ->default(50)
                            ->suffix('%')
                            ->minValue(10)
                            ->maxValue(90)
                            ->helperText('Porcentaje de usuarios que verán la variante'),

                        Forms\Components\TextInput::make('confidence_level')
                            ->label('Nivel de Confianza')
                            ->numeric()
                            ->default(95.00)
                            ->suffix('%')
                            ->minValue(80)
                            ->maxValue(99.9)
                            ->step(0.1),

                        Forms\Components\TextInput::make('min_sample_size')
                            ->label('Tamaño Mínimo de Muestra')
                            ->numeric()
                            ->default(100)
                            ->minValue(50)
                            ->helperText('Mínimo de conversiones por grupo'),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duración Planificada')
                            ->numeric()
                            ->default(30)
                            ->suffix('días')
                            ->minValue(7)
                            ->maxValue(365),
                    ])->columns(2),

                Section::make('Métricas Objetivo')
                    ->schema([
                        Forms\Components\CheckboxList::make('target_metrics')
                            ->label('Métricas a Medir')
                            ->options([
                                'conversion_rate' => 'Tasa de Conversión',
                                'revenue_per_user' => 'Ingresos por Usuario',
                                'churn_rate' => 'Tasa de Cancelación',
                                'trial_conversion' => 'Conversión de Trial',
                                'customer_lifetime_value' => 'Valor de Vida del Cliente',
                            ])
                            ->default(['conversion_rate', 'revenue_per_user'])
                            ->required()
                            ->columns(2),
                    ]),

                Section::make('Notas y Configuración')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activo',
                                'paused' => 'Pausado',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(3)
                            ->placeholder('Observaciones, cambios realizados, etc.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Experimento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('controlPlan.name')
                    ->label('Control')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('variantPlan.name')
                    ->label('Variante')
                    ->searchable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'warning',
                        'paused' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('traffic_split')
                    ->label('Split')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('getDaysRunning')
                    ->label('Días')
                    ->suffix(' días')
                    ->sortable(),

                Tables\Columns\TextColumn::make('confidence_level')
                    ->label('Confianza')
                    ->suffix('%')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('winner')
                    ->label('Ganador')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'control' => 'success',
                        'variant' => 'info',
                        'inconclusive' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'control' => 'Control',
                        'variant' => 'Variante',
                        'inconclusive' => 'Sin Conclusión',
                        default => 'Pendiente',
                    })
                    ->placeholder('Pendiente'),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Finalizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('winner')
                    ->label('Resultado')
                    ->options([
                        'control' => 'Control Ganó',
                        'variant' => 'Variante Ganó',
                        'inconclusive' => 'Sin Conclusión',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),

                Actions\Action::make('start_experiment')
                    ->label('Iniciar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Experimento A/B')
                    ->modalDescription('¿Estás seguro de que quieres iniciar este experimento? Una vez iniciado, los usuarios empezarán a ver la variante.')
                    ->visible(fn (PlanExperiment $record) => $record->status === 'draft')
                    ->action(function (PlanExperiment $record) {
                        $record->update([
                            'status' => 'active',
                            'started_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Experimento iniciado')
                            ->body("El experimento '{$record->name}' ha sido iniciado exitosamente")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('pause_experiment')
                    ->label('Pausar')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (PlanExperiment $record) => $record->status === 'active')
                    ->action(function (PlanExperiment $record) {
                        $record->update(['status' => 'paused']);

                        Notification::make()
                            ->title('Experimento pausado')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('complete_experiment')
                    ->label('Finalizar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar Experimento')
                    ->modalDescription('Se calcularán los resultados finales y el experimento se marcará como completado.')
                    ->visible(fn (PlanExperiment $record) => in_array($record->status, ['active', 'paused']))
                    ->action(function (PlanExperiment $record) {
                        // Calcular resultados finales
                        $results = $record->calculateResults();

                        $record->update([
                            'status' => 'completed',
                            'ended_at' => now(),
                            'results' => $results,
                            'statistical_significance' => $results['confidence'],
                            'winner' => $results['winner'],
                        ]);

                        Notification::make()
                            ->title('Experimento completado')
                            ->body("Ganador: " . match($results['winner']) {
                                'control' => 'Plan Control',
                                'variant' => 'Plan Variante',
                                default => 'Sin conclusión definitiva'
                            })
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('view_results')
                    ->label('Ver Resultados')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->visible(fn (PlanExperiment $record) => $record->status === 'completed')
                    ->url(fn (PlanExperiment $record): string => static::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SuperAdmin\Resources\PlanExperiments\Pages\ListPlanExperiments::route('/'),
            'create' => \App\Filament\SuperAdmin\Resources\PlanExperiments\Pages\CreatePlanExperiment::route('/create'),
            'view' => \App\Filament\SuperAdmin\Resources\PlanExperiments\Pages\ViewPlanExperiment::route('/{record}'),
            'edit' => \App\Filament\SuperAdmin\Resources\PlanExperiments\Pages\EditPlanExperiment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }
}