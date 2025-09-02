<?php

namespace App\Enums;

enum FinishingMeasurementUnit: string
{
    case MILLAR = 'millar';
    case RANGO = 'rango';
    case UNIDAD = 'unidad';
    case TAMAÑO = 'tamaño';

    public function label(): string
    {
        return match ($this) {
            self::MILLAR => 'Millar (1000 unidades)',
            self::RANGO => 'Rango (Entre cantidades)',
            self::UNIDAD => 'Unidad',
            self::TAMAÑO => 'Tamaño (Ancho x Alto)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MILLAR => 'Cálculo: ceil(cantidad ÷ 1000) × precio_millar',
            self::RANGO => 'Cálculo: Buscar rango según cantidad',
            self::UNIDAD => 'Cálculo: cantidad × precio_unitario',
            self::TAMAÑO => 'Cálculo: ancho × alto × precio_unitario',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}