<?php

namespace App\Enums;

enum CollectionAccountStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::APPROVED => 'Aprobada',
            self::PAID => 'Pagada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'warning',
            self::APPROVED => 'info',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SENT,
            self::APPROVED,
        ]);
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::DRAFT => in_array($newStatus, [self::SENT, self::CANCELLED]),
            self::SENT => in_array($newStatus, [self::APPROVED, self::CANCELLED]),
            self::APPROVED => in_array($newStatus, [self::PAID, self::CANCELLED]),
            self::PAID => false,
            self::CANCELLED => false,
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SENT,
            self::APPROVED,
        ]);
    }
}
