<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class PurchaseOrderDigest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
        public Collection $recentOrders,
        public Collection $pendingOrders
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $today = now()->format('d/m/Y');

        return (new MailMessage)
                    ->subject("Resumen Diario de Órdenes - {$today}")
                    ->markdown('emails.purchase-order.digest', [
                        'company' => $this->company,
                        'recentOrders' => $this->recentOrders,
                        'pendingOrders' => $this->pendingOrders,
                        'user' => $notifiable,
                        'today' => $today,
                    ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'recent_orders_count' => $this->recentOrders->count(),
            'pending_orders_count' => $this->pendingOrders->count(),
            'total_pending_amount' => $this->pendingOrders->sum('total_amount'),
            'message' => "Resumen diario: {$this->recentOrders->count()} órdenes nuevas, {$this->pendingOrders->count()} pendientes"
        ];
    }
}