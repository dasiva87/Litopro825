<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductionStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviada',
            self::RECEIVED => 'Recibida',
            self::IN_PROGRESS => 'En Proceso',
            self::ON_HOLD => 'En Pausa',
            self::COMPLETED => 'Finalizada',
            self::CANCELLED => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::RECEIVED => 'primary',
            self::IN_PROGRESS => 'warning',
            self::ON_HOLD => 'gray',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::SENT => 'heroicon-o-paper-airplane',
            self::RECEIVED => 'heroicon-o-inbox-arrow-down',
            self::IN_PROGRESS => 'heroicon-o-cog-6-tooth',
            self::ON_HOLD => 'heroicon-o-pause-circle',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Transiciones permitidas para órdenes PROPIAS (producción interna)
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::ON_HOLD, self::COMPLETED, self::CANCELLED]),
            self::ON_HOLD => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
            // Estados de órdenes enviadas/recibidas (gestionados por receptor)
            self::SENT => false,
            self::RECEIVED => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
        };
    }

    /**
     * Transiciones permitidas para órdenes RECIBIDAS (yo soy el proveedor)
     */
    public function canTransitionToAsReceiver(self $newStatus): bool
    {
        return match ($this) {
            self::SENT => in_array($newStatus, [self::RECEIVED, self::CANCELLED]),
            self::RECEIVED => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::ON_HOLD, self::COMPLETED, self::CANCELLED]),
            self::ON_HOLD => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
            self::DRAFT => false,
        };
    }

    public static function getTransitionableStatuses(self $currentStatus, bool $isReceiver = false): array
    {
        return array_filter(
            self::cases(),
            fn (self $status) => $isReceiver
                ? $currentStatus->canTransitionToAsReceiver($status)
                : $currentStatus->canTransitionTo($status)
        );
    }

    /**
     * Estados visibles para órdenes propias
     */
    public static function getOwnOrderStatuses(): array
    {
        return [
            self::DRAFT,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * Estados visibles para órdenes enviadas a proveedores
     */
    public static function getSentOrderStatuses(): array
    {
        return [
            self::DRAFT,
            self::SENT,
            self::RECEIVED,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * Estados visibles para órdenes recibidas de clientes
     */
    public static function getReceivedOrderStatuses(): array
    {
        return [
            self::SENT,
            self::RECEIVED,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }
}
