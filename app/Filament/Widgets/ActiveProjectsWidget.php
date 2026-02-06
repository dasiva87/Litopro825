<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActiveProjectsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    protected static ?string $heading = 'Proyectos Activos';

    protected ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->forCurrentTenant()
                    ->whereIn('status', [
                        ProjectStatus::ACTIVE,
                        ProjectStatus::IN_PROGRESS,
                    ])
                    ->with(['contact'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Codigo')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->limit(25),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (ProjectStatus $state) => $state->getLabel())
                    ->color(fn (ProjectStatus $state) => $state->getColor()),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Docs')
                    ->counts('documents')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('purchase_orders_count')
                    ->label('Pedidos')
                    ->counts('purchaseOrders')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('production_orders_count')
                    ->label('Produccion')
                    ->counts('productionOrders')
                    ->alignCenter(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn (Project $record) => route('filament.admin.resources.projects.view', $record)),
            ])
            ->emptyStateHeading('Sin proyectos activos')
            ->emptyStateDescription('Crea un proyecto para agrupar cotizaciones relacionadas.')
            ->emptyStateIcon('heroicon-o-folder')
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }
}
