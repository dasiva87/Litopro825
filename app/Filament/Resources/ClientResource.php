<?php

namespace App\Filament\Resources;

use App\Enums\NavigationGroup;
use App\Models\Contact;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Contactos;

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return true; // Temporalmente permitir acceso para debug
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->isGrafired() ?
                        '🏢 '.$record->linkedCompany?->name :
                        '📍 Local'
                    ),

                TextColumn::make('is_local')
                    ->label('Origen')
                    ->formatStateUsing(fn ($state) => $state ? 'Local' : 'Grafired')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'success'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('local')
                    ->label('Solo Locales')
                    ->query(fn (Builder $query) => $query->local()),

                Filter::make('grafired')
                    ->label('Solo Grafired')
                    ->query(fn (Builder $query) => $query->grafired()),

                Filter::make('active')
                    ->label('Solo Activos')
                    ->query(fn (Builder $query) => $query->active()),
            ])
            ->actions([
                // Acciones para clientes LOCALES (propios)
                Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.contacts.edit', $record))
                    ->visible(fn ($record) => $record->is_local)
                    ->color('primary'),

                // Acciones para clientes GRAFIRED (enlazados)
                Action::make('view_company')
                    ->label('Ver Empresa')
                    ->icon('heroicon-o-building-office')
                    ->url(fn ($record) => $record->linkedCompany ?
                        route('filament.admin.pages.companies') :
                        null
                    )
                    ->visible(fn ($record) => $record->isGrafired() && $record->linkedCompany)
                    ->openUrlInNewTab(),

                Action::make('sync_data')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn ($record) => $record->syncFromLinkedCompany())
                    ->visible(fn ($record) => $record->isGrafired())
                    ->successNotificationTitle('Datos sincronizados correctamente'),

                Action::make('view_grafired')
                    ->label('Ver/Editar')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.contacts.edit', $record))
                    ->visible(fn ($record) => $record->isGrafired())
                    ->color('info'),
            ])
            ->headerActions([

                Action::make('search_grafired')
                    ->label('Buscar en Grafired')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('success')
                    ->visible(false), // Se implementará en siguiente fase
            ])
            ->emptyStateActions([
                Action::make('create_first_client')
                    ->label('Crear Primer Cliente')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => route('filament.admin.resources.contacts.create', ['type' => 'customer', 'is_local' => true])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['linkedCompany'])
            ->customers() // Solo clientes
            ->forCurrentTenant(); // Filtro por empresa
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Pages\Clients\ListClients::route('/'),
        ];
    }
}
