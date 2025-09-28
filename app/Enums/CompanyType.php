<?php

namespace App\Enums;

enum CompanyType: string
{
    case LITOGRAFIA = 'litografia';
    case PAPELERIA = 'papeleria';

    public function label(): string
    {
        return match($this) {
            self::LITOGRAFIA => 'Litografía',
            self::PAPELERIA => 'Papelería',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::LITOGRAFIA => 'Empresa especializada en servicios de impresión y producción gráfica',
            self::PAPELERIA => 'Empresa dedicada a la venta de papeles y productos de oficina',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function allowedResources(): array
    {
        return match($this) {
            self::LITOGRAFIA => [
                'contacts',
                'papers',
                'printing-machines',
                'products',
                'simple-items',
                'digital-items',
                'talonario-items',
                'magazine-items',
                'documents',
                'finishings',
                'stock-management',
                'stock-movements',
                'supplier-relationships',
                'supplier-requests',
                'users',
                'roles',
                'social-feed',
                'billing',
                'company-settings',
                'subscriptions',
                'plans'
            ],
            self::PAPELERIA => [
                'contacts',
                'papers',
                'products',
                'documents', // Solo cotizaciones
                'stock-management',
                'stock-movements',
                'supplier-requests',
                'users',
                'roles',
                'social-feed',
                'billing',
                'company-settings',
                'subscriptions',
                'plans'
            ],
        };
    }
}