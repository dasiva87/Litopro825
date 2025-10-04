<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case CONFIRMED = 'confirmed';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::CONFIRMED => 'Confirmada',
            self::RECEIVED => 'Recibida',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::CONFIRMED => 'warning',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::SENT => 'heroicon-o-paper-airplane',
            self::CONFIRMED => 'heroicon-o-check-circle',
            self::RECEIVED => 'heroicon-o-archive-box',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::SENT, self::CANCELLED]),
            self::SENT => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::RECEIVED, self::CANCELLED]),
            self::RECEIVED => false,
            self::CANCELLED => false,
        };
    }

    public function getNextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::SENT, self::CANCELLED],
            self::SENT => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::RECEIVED, self::CANCELLED],
            self::RECEIVED => [],
            self::CANCELLED => [],
        };
    }
}
