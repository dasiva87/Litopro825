<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_RECEIVED = 'partially_received';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::CONFIRMED => 'Confirmada',
            self::PARTIALLY_RECEIVED => 'Parcialmente Recibida',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'warning',
            self::CONFIRMED => 'info',
            self::PARTIALLY_RECEIVED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SENT,
            self::CONFIRMED,
            self::PARTIALLY_RECEIVED
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this === self::DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SENT,
            self::CONFIRMED
        ]);
    }
}