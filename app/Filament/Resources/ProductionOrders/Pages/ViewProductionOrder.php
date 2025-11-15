<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Enums\ProductionStatus;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use App\Services\ProductionOrderPdfService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProductionOrder extends ViewRecord
{
    protected static string $resource = ProductionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('view_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn ($record) => route('production-orders.pdf', $record))
                ->openUrlInNewTab(),

            Action::make('duplicate')
                ->label('Duplicar Orden')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Duplicar Orden de Producción')
                ->modalDescription('Se creará una copia exacta de esta orden en estado Borrador con un nuevo número de orden.')
                ->modalSubmitActionLabel('Duplicar')
                ->action(function ($record) {
                    $newOrder = $record->duplicate();

                    Notification::make()
                        ->success()
                        ->title('Orden Duplicada')
                        ->body("Nueva orden #{$newOrder->production_number} creada exitosamente.")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('Ver Orden')
                                ->url(route('filament.admin.resources.production-orders.edit', $newOrder))
                        ])
                        ->send();
                }),

            Action::make('send_email')
                ->label('Enviar por Email')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->form([
                    Components\TextInput::make('recipient_email')
                        ->label('Email del Destinatario')
                        ->email()
                        ->required()
                        ->default(function ($record) {
                            // Prioridad: operador, luego proveedor
                            if ($record->operator && $record->operator->email) {
                                return $record->operator->email;
                            }
                            if ($record->supplier && $record->supplier->email) {
                                return $record->supplier->email;
                            }
                            return null;
                        })
                        ->helperText('Email del operador o proveedor'),

                    Components\Textarea::make('custom_message')
                        ->label('Mensaje Personalizado')
                        ->placeholder('Mensaje adicional para incluir en el email...')
                        ->rows(3),
                ])
                ->modalHeading('Enviar Orden por Email')
                ->modalDescription(fn ($record) => "Enviar orden #{$record->production_number} con PDF adjunto")
                ->action(function ($record, array $data) {
                    try {
                        $pdfService = app(ProductionOrderPdfService::class);
                        $pdf = $pdfService->generatePdf($record);

                        // Enviar email
                        \Illuminate\Support\Facades\Mail::send('emails.production-order', [
                            'order' => $record,
                            'company' => $record->company,
                            'operator' => $record->operator,
                            'supplier' => $record->supplier,
                            'customMessage' => $data['custom_message'] ?? null,
                        ], function ($message) use ($record, $data, $pdf) {
                            $message->to($data['recipient_email'])
                                ->subject("Orden de Producción #{$record->production_number} - {$record->company->name}")
                                ->attachData($pdf->output(), "orden-produccion-{$record->production_number}.pdf", [
                                    'mime' => 'application/pdf',
                                ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Email Enviado')
                            ->body("Orden enviada exitosamente a {$data['recipient_email']}")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al Enviar Email')
                            ->body('No se pudo enviar el email. Verifica la configuración SMTP.')
                            ->send();

                        \Log::error('Error sending production order email', [
                            'order_id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }),

            Action::make('queue')
                ->label('Poner en Cola')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === ProductionStatus::DRAFT && $record->total_items > 0)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::QUEUED)) {
                        Notification::make()->success()->title('Orden en Cola')->send();
                    }
                }),

            Action::make('start_production')
                ->label('Iniciar Producción')
                ->icon('heroicon-o-play')
                ->color('success')
                ->form([
                    Components\Select::make('supplier_id')
                        ->label('Proveedor')
                        ->relationship(
                            name: 'supplier',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query
                                ->where('company_id', auth()->user()->company_id)
                                ->whereIn('type', ['supplier', 'both'])
                        )
                        ->required(),
                    Components\Select::make('operator_user_id')
                        ->label('Operador')
                        ->relationship(
                            name: 'operator',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('company_id', auth()->user()->company_id)
                        )
                        ->required(),
                ])
                ->visible(fn ($record) => $record->status === ProductionStatus::QUEUED)
                ->action(function ($record, array $data) {
                    $record->update($data);
                    if ($record->changeStatus(ProductionStatus::IN_PROGRESS)) {
                        Notification::make()->success()->title('Producción Iniciada')->send();
                    }
                }),

            Action::make('pause_production')
                ->label('Pausar Producción')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->form([
                    Components\Textarea::make('pause_reason')
                        ->label('Motivo de la Pausa')
                        ->placeholder('Ej: Falta de material, problema con máquina, cambio de prioridad...')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn ($record) => $record->status === ProductionStatus::IN_PROGRESS)
                ->action(function ($record, array $data) {
                    // Guardar el motivo en las notas del operador
                    $currentNotes = $record->operator_notes ?? '';
                    $pauseNote = "\n[PAUSA - " . now()->format('d/m/Y H:i') . "]: " . $data['pause_reason'];
                    $record->operator_notes = $currentNotes . $pauseNote;
                    $record->save();

                    if ($record->changeStatus(ProductionStatus::ON_HOLD)) {
                        Notification::make()
                            ->success()
                            ->title('Producción Pausada')
                            ->body('La orden ha sido pausada exitosamente.')
                            ->send();
                    }
                }),

            Action::make('resume_production')
                ->label('Reanudar Producción')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Reanudar Producción')
                ->modalDescription('¿Deseas reanudar esta orden de producción?')
                ->visible(fn ($record) => $record->status === ProductionStatus::ON_HOLD)
                ->action(function ($record) {
                    // Registrar la reanudación
                    $currentNotes = $record->operator_notes ?? '';
                    $resumeNote = "\n[REANUDADA - " . now()->format('d/m/Y H:i') . "]";
                    $record->operator_notes = $currentNotes . $resumeNote;
                    $record->save();

                    if ($record->changeStatus(ProductionStatus::QUEUED)) {
                        Notification::make()
                            ->success()
                            ->title('Producción Reanudada')
                            ->body('La orden volvió a la cola de producción.')
                            ->send();
                    }
                }),

            Action::make('complete_production')
                ->label('Completar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === ProductionStatus::IN_PROGRESS)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::COMPLETED)) {
                        Notification::make()->success()->title('Producción Completada')->send();
                    }
                }),
        ];
    }
}
