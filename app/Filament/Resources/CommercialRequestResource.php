<?php

namespace App\Filament\Resources;

use App\Enums\NavigationGroup;
use App\Filament\Resources\CommercialRequests\Schemas\CommercialRequestViewSchema;
use App\Models\CommercialRequest;
use App\Services\CommercialRequestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CommercialRequestResource extends Resource
{
    protected static ?string $model = CommercialRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Solicitudes Comerciales';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Contactos;

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        if (!auth()->check() || !auth()->user()->company_id) {
            return null;
        }

        $companyId = auth()->user()->company_id;

        // Contar todas las solicitudes pendientes de la empresa (enviadas y recibidas)
        $count = static::getModel()::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($companyId) {
                $query->where('requester_company_id', $companyId)
                    ->orWhere('target_company_id', $companyId);
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canViewAny(): bool
    {
        return true; // Temporalmente permitir acceso para debug
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        // La creación se manejará desde otros módulos
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CommercialRequestViewSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('relationship_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'client' => 'Cliente',
                        'supplier' => 'Proveedor',
                        default => 'Desconocido'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'client' => 'primary',
                        'supplier' => 'warning',
                        default => 'gray'
                    }),

                TextColumn::make('requester_company_name')
                    ->label('Empresa Solicitante')
                    ->getStateUsing(fn ($record) => $record->requesterCompany->name)
                    ->description(fn ($record) => $record->requesterCompany->email)
                    ->searchable(),

                TextColumn::make('target_company_name')
                    ->label('Empresa Objetivo')
                    ->getStateUsing(fn ($record) => $record->targetCompany->name)
                    ->description(fn ($record) => $record->targetCompany->email)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => 'Desconocido'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    }),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('responded_at')
                    ->label('Respondida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    ]),

                SelectFilter::make('relationship_type')
                    ->label('Tipo')
                    ->options([
                        'client' => 'Cliente',
                        'supplier' => 'Proveedor',
                    ]),

                Filter::make('received')
                    ->label('Recibidas')
                    ->query(fn (Builder $query) => $query->forTarget(auth()->user()->company_id)),

                Filter::make('sent')
                    ->label('Enviadas')
                    ->query(fn (Builder $query) => $query->fromRequester(auth()->user()->company_id)),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->form([
                        Textarea::make('response_message')
                            ->label('Mensaje de respuesta')
                            ->placeholder('Mensaje opcional para la empresa solicitante...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(CommercialRequestService::class);

                        try {
                            $service->approveRequest(
                                $record,
                                auth()->user(),
                                $data['response_message'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title('Solicitud aprobada')
                                ->body('Se ha creado la relación comercial automáticamente')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->isPending() &&
                        $record->target_company_id === auth()->user()->company_id
                    ),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('response_message')
                            ->label('Motivo del rechazo')
                            ->placeholder('Explica por qué rechazas esta solicitud...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(CommercialRequestService::class);

                        try {
                            $service->rejectRequest(
                                $record,
                                auth()->user(),
                                $data['response_message']
                            );

                            Notification::make()
                                ->warning()
                                ->title('Solicitud rechazada')
                                ->body('La empresa solicitante será notificada')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->isPending() &&
                        $record->target_company_id === auth()->user()->company_id
                    ),

                Action::make('view_response')
                    ->label('Ver Respuesta')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Respuesta a la Solicitud')
                    ->modalContent(fn ($record) => view('filament.modals.commercial-request-response', compact('record')))
                    ->visible(fn ($record) => $record->response_message && ! $record->isPending()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => route('filament.admin.resources.commercial-requests.view', ['record' => $record]));
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = auth()->user()->company_id;

        return parent::getEloquentQuery()
            ->with(['requesterCompany', 'targetCompany', 'requestedByUser', 'respondedByUser'])
            ->where(function ($query) use ($companyId) {
                $query->where('requester_company_id', $companyId)
                    ->orWhere('target_company_id', $companyId);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Pages\CommercialRequests\ListCommercialRequests::route('/'),
            'view' => \App\Filament\Pages\CommercialRequests\ViewCommercialRequest::route('/{record}'),
        ];
    }
}
