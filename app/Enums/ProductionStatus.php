<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case DRAFT = 'draft';
    case QUEUED = 'queued';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::QUEUED => 'En Cola',
            self::IN_PROGRESS => 'En Producción',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
            self::ON_HOLD => 'En Espera',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::QUEUED => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::ON_HOLD => 'secondary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::QUEUED => 'heroicon-o-clock',
            self::IN_PROGRESS => 'heroicon-o-cog-6-tooth',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::ON_HOLD => 'heroicon-o-pause-circle',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::QUEUED, self::CANCELLED]),
            self::QUEUED => in_array($newStatus, [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::ON_HOLD, self::CANCELLED]),
            self::ON_HOLD => in_array($newStatus, [self::QUEUED, self::CANCELLED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
        };
    }

    public static function getTransitionableStatuses(self $currentStatus): array
    {
        return array_filter(
            self::cases(),
            fn (self $status) => $currentStatus->canTransitionTo($status)
        );
    }
}
