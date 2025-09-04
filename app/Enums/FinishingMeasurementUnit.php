<?php

namespace App\Enums;

enum FinishingMeasurementUnit: string
{
    case MILLAR = 'millar';
    case RANGO = 'rango';
    case UNIDAD = 'unidad';
    case TAMAÑO = 'tamaño';
    
    // Nuevos para talonarios
    case POR_NUMERO = 'por_numero';
    case POR_TALONARIO = 'por_talonario';

    public function label(): string
    {
        return match ($this) {
            self::MILLAR => 'Millar (1000 unidades)',
            self::RANGO => 'Rango (Entre cantidades)',
            self::UNIDAD => 'Unidad',
            self::TAMAÑO => 'Tamaño (Ancho x Alto)',
            self::POR_NUMERO => 'Por Número (Numeración)',
            self::POR_TALONARIO => 'Por Talonario (Bloques)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MILLAR => 'Cálculo: ceil(cantidad ÷ 1000) × precio_millar',
            self::RANGO => 'Cálculo: Buscar rango según cantidad',
            self::UNIDAD => 'Cálculo: cantidad × precio_unitario',
            self::TAMAÑO => 'Cálculo: ancho × alto × precio_unitario',
            self::POR_NUMERO => 'Cálculo: total_números × precio_por_numero',
            self::POR_TALONARIO => 'Cálculo: cantidad_talonarios × precio_por_talonario',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}