<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\SupplierRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuppliersRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierRelationships';

    protected static ?string $recordTitleAttribute = 'supplier_company_id';

    protected static ?string $title = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('supplierCompany.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplierCompany.email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('supplierCompany.phone')
                    ->label('Teléfono'),

                TextColumn::make('is_active')
                    ->label('Estado')
                    ->getStateUsing(fn ($record) => $record->is_active ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state === 'Activo' ? 'success' : 'danger'),

                TextColumn::make('approved_at')
                    ->label('Aprobado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('request_supplier')
                    ->label('Solicitar Proveedor')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Select::make('supplier_company_id')
                            ->label('Proveedor')
                            ->options(function () {
                                return Company::papelerias()
                                    ->active()
                                    ->whereNotIn('id', function ($query) {
                                        // Excluir papelerías ya solicitadas o relacionadas
                                        $currentCompanyId = auth()->user()->company_id;
                                        $query->select('supplier_company_id')
                                            ->from('supplier_requests')
                                            ->where('requester_company_id', $currentCompanyId)
                                            ->where('status', '!=', 'rejected')
                                            ->union(
                                                \DB::table('supplier_relationships')
                                                    ->select('supplier_company_id')
                                                    ->where('client_company_id', $currentCompanyId)
                                                    ->where('is_active', true)
                                            );
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),

                        Textarea::make('message')
                            ->label('Mensaje (opcional)')
                            ->placeholder('Descripción de por qué necesita esta papelería como proveedor')
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $currentCompanyId = auth()->user()->company_id;

                        // Crear solicitud
                        SupplierRequest::create([
                            'requester_company_id' => $currentCompanyId,
                            'supplier_company_id' => $data['supplier_company_id'],
                            'requested_by_user_id' => auth()->id(),
                            'message' => $data['message'] ?? null,
                            'status' => 'pending',
                        ]);

                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('Se ha enviado la solicitud de proveedor exitosamente.')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        // Solo visible para litografías
                        return auth()->user()->company->isLitografia();
                    }),
            ])
            ->actions([
                Action::make('deactivate')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->deactivate())
                    ->visible(fn ($record) => $record->is_active),

                Action::make('reactivate')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->reactivate())
                    ->visible(fn ($record) => !$record->is_active),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Solo mostrar proveedores activos por defecto
                return $query->with(['supplierCompany']);
            });
    }
}