<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CollectionAccountStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::APPROVED => 'Aprobada',
            self::PAID => 'Pagada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::APPROVED => 'warning',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::DRAFT => 'heroicon-o-document',
            self::SENT => 'heroicon-o-paper-airplane',
            self::APPROVED => 'heroicon-o-check-circle',
            self::PAID => 'heroicon-o-banknotes',
            self::CANCELLED => 'heroicon-o-x-circle',
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
