<?php

namespace App\Filament\SuperAdmin\Resources\PlanExperiments\Pages;

use App\Filament\SuperAdmin\Resources\PlanExperiments\PlanExperimentResource;
use App\Models\PlanExperiment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPlanExperiment extends ViewRecord
{
    protected static string $resource = PlanExperimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('start_experiment')
                ->label('Iniciar Experimento')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (PlanExperiment $record) => $record->status === 'draft')
                ->action(function (PlanExperiment $record) {
                    $record->update([
                        'status' => 'active',
                        'started_at' => now(),
                    ]);
                }),

            Actions\Action::make('complete_experiment')
                ->label('Finalizar Experimento')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (PlanExperiment $record) => in_array($record->status, ['active', 'paused']))
                ->action(function (PlanExperiment $record) {
                    $results = $record->calculateResults();
                    $record->update([
                        'status' => 'completed',
                        'ended_at' => now(),
                        'results' => $results,
                        'statistical_significance' => $results['confidence'],
                        'winner' => $results['winner'],
                    ]);
                }),

            Actions\Action::make('export_results')
                ->label('Exportar Resultados')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn (PlanExperiment $record) => $record->status === 'completed')
                ->action(function (PlanExperiment $record) {
                    // Implementar exportación a CSV o PDF
                    return response()->download(storage_path('app/exports/experiment-' . $record->id . '.csv'));
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Experimento')
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activo',
                                'paused' => 'Pausado',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->disabled(),
                    ])->columns(2),

                Section::make('Configuración del Experimento')
                    ->schema([
                        Forms\Components\Select::make('control_plan_id')
                            ->label('Plan Control')
                            ->relationship('controlPlan', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('variant_plan_id')
                            ->label('Plan Variante')
                            ->relationship('variantPlan', 'name')
                            ->disabled(),

                        Forms\Components\TextInput::make('traffic_split')
                            ->label('División de Tráfico (%)')
                            ->disabled(),

                        Forms\Components\TextInput::make('confidence_level')
                            ->label('Nivel de Confianza (%)')
                            ->disabled(),

                        Forms\Components\TextInput::make('min_sample_size')
                            ->label('Tamaño Mínimo de Muestra')
                            ->disabled(),

                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duración Planificada (días)')
                            ->disabled(),
                    ])->columns(3),

                Section::make('Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Fecha de Inicio')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('ended_at')
                            ->label('Fecha de Finalización')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha de Creación')
                            ->disabled(),
                    ])->columns(3),

                Section::make('Resultados')
                    ->schema([
                        Forms\Components\TextInput::make('statistical_significance')
                            ->label('Significancia Estadística (%)')
                            ->disabled(),

                        Forms\Components\Select::make('winner')
                            ->label('Ganador')
                            ->options([
                                'control' => 'Plan Control',
                                'variant' => 'Plan Variante',
                                'inconclusive' => 'Sin Conclusión',
                            ])
                            ->disabled(),
                    ])->columns(2)
                    ->visible(fn (?PlanExperiment $record) => $record && $record->status === 'completed'),

                Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}