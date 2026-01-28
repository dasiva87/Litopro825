<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\Project;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class AllDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Todos';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getCombinedQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type_label')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cotización' => 'primary',
                        'Orden de Pedido' => 'info',
                        'Orden de Producción' => 'warning',
                        'Cuenta de Cobro' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('related_name')
                    ->label('Cliente/Proveedor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($record): string => $this->getStatusColor($record->status_raw ?? 'draft')),

                Tables\Columns\TextColumn::make('doc_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('COP')
                    ->alignEnd(),
            ])
            ->defaultSort('doc_date', 'desc')
            ->actions([
                Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->view_url),
            ]);
    }

    protected function getCombinedQuery(): Builder
    {
        $project = $this->getOwnerRecord();

        if (!$project instanceof Project) {
            return \App\Models\CombinedDocument::query()->whereRaw('1 = 0');
        }

        // Crear una consulta UNION de todos los documentos
        // Cada registro tiene un ID único usando CONCAT(tipo, id_original)
        $documents = \DB::table('documents')
            ->select([
                \DB::raw("CONCAT('doc_', id) as id"),
                \DB::raw("'document' as doc_type"),
                \DB::raw("'Cotización' as type_label"),
                'document_number as number',
                \DB::raw("(SELECT name FROM contacts WHERE contacts.id = documents.contact_id LIMIT 1) as related_name"),
                'status as status_raw',
                \DB::raw("CASE status
                    WHEN 'draft' THEN 'Borrador'
                    WHEN 'sent' THEN 'Enviada'
                    WHEN 'approved' THEN 'Aprobada'
                    WHEN 'rejected' THEN 'Rechazada'
                    WHEN 'in_production' THEN 'En Producción'
                    WHEN 'completed' THEN 'Completada'
                    WHEN 'cancelled' THEN 'Cancelada'
                    ELSE status
                END as status_label"),
                'date as doc_date',
                'total as amount',
                'id as record_id',
                \DB::raw("CONCAT('/admin/documents/', id) as view_url"),
            ])
            ->where('project_id', $project->id)
            ->whereNull('deleted_at');

        $purchaseOrders = \DB::table('purchase_orders')
            ->select([
                \DB::raw("CONCAT('po_', id) as id"),
                \DB::raw("'purchase_order' as doc_type"),
                \DB::raw("'Orden de Pedido' as type_label"),
                'order_number as number',
                \DB::raw("(SELECT name FROM contacts WHERE contacts.id = purchase_orders.supplier_id LIMIT 1) as related_name"),
                'status as status_raw',
                \DB::raw("CASE status
                    WHEN 'draft' THEN 'Borrador'
                    WHEN 'sent' THEN 'Enviada'
                    WHEN 'confirmed' THEN 'Confirmada'
                    WHEN 'in_progress' THEN 'En Proceso'
                    WHEN 'completed' THEN 'Completada'
                    WHEN 'cancelled' THEN 'Cancelada'
                    ELSE status
                END as status_label"),
                'order_date as doc_date',
                'total_amount as amount',
                'id as record_id',
                \DB::raw("CONCAT('/admin/purchase-orders/', id) as view_url"),
            ])
            ->where('project_id', $project->id);

        $productionOrders = \DB::table('production_orders')
            ->select([
                \DB::raw("CONCAT('prod_', id) as id"),
                \DB::raw("'production_order' as doc_type"),
                \DB::raw("'Orden de Producción' as type_label"),
                'production_number as number',
                \DB::raw("(SELECT name FROM contacts WHERE contacts.id = production_orders.supplier_id LIMIT 1) as related_name"),
                'status as status_raw',
                \DB::raw("CASE status
                    WHEN 'draft' THEN 'Borrador'
                    WHEN 'sent' THEN 'Enviada'
                    WHEN 'received' THEN 'Recibida'
                    WHEN 'in_progress' THEN 'En Proceso'
                    WHEN 'completed' THEN 'Completada'
                    WHEN 'cancelled' THEN 'Cancelada'
                    WHEN 'on_hold' THEN 'En Espera'
                    ELSE status
                END as status_label"),
                \DB::raw("COALESCE(scheduled_date, created_at) as doc_date"),
                \DB::raw("NULL as amount"),
                'id as record_id',
                \DB::raw("CONCAT('/admin/production-orders/', id) as view_url"),
            ])
            ->where('project_id', $project->id)
            ->whereNull('deleted_at');

        $collectionAccounts = \DB::table('collection_accounts')
            ->select([
                \DB::raw("CONCAT('ca_', id) as id"),
                \DB::raw("'collection_account' as doc_type"),
                \DB::raw("'Cuenta de Cobro' as type_label"),
                'account_number as number',
                \DB::raw("(SELECT name FROM contacts WHERE contacts.id = collection_accounts.contact_id LIMIT 1) as related_name"),
                'status as status_raw',
                \DB::raw("CASE status
                    WHEN 'draft' THEN 'Borrador'
                    WHEN 'sent' THEN 'Enviada'
                    WHEN 'approved' THEN 'Aprobada'
                    WHEN 'paid' THEN 'Pagada'
                    WHEN 'cancelled' THEN 'Cancelada'
                    ELSE status
                END as status_label"),
                'issue_date as doc_date',
                'total_amount as amount',
                'id as record_id',
                \DB::raw("CONCAT('/admin/collection-accounts/', id) as view_url"),
            ])
            ->where('project_id', $project->id);

        // Combinar todas las consultas
        $union = $documents
            ->union($purchaseOrders)
            ->union($productionOrders)
            ->union($collectionAccounts);

        // Usar modelo limpio sin scopes ni ordenamiento predeterminado
        return \App\Models\CombinedDocument::query()
            ->fromSub($union, 'combined_docs')
            ->select('*');
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'sent' => 'info',
            'approved', 'confirmed' => 'success',
            'in_progress', 'received' => 'warning',
            'completed', 'paid' => 'success',
            'cancelled', 'rejected' => 'danger',
            'on_hold' => 'gray',
            default => 'secondary',
        };
    }
}
