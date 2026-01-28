<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::ACTIVE => 'Activo',
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
            self::ON_HOLD => 'En Espera',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'info',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::ON_HOLD => 'secondary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::ACTIVE => 'heroicon-o-play',
            self::IN_PROGRESS => 'heroicon-o-clock',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::ON_HOLD => 'heroicon-o-pause',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($newStatus, [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::ON_HOLD, self::CANCELLED]),
            self::ON_HOLD => in_array($newStatus, [self::ACTIVE, self::IN_PROGRESS, self::CANCELLED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
        };
    }

    public function getNextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::ON_HOLD, self::CANCELLED],
            self::ON_HOLD => [self::ACTIVE, self::IN_PROGRESS, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }
}
