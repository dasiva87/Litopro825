<?php

namespace App\Enums;

enum NavigationGroup: string
{
    case Documentos = 'documentos';
    case Items = 'items';
    case Inventario = 'inventario';
    case Configuracion = 'configuracion';
    case Sistema = 'sistema';

    public function getLabel(): string
    {
        return match ($this) {
            self::Documentos => 'Documentos',
            self::Items => 'Items',
            self::Inventario => 'Inventario',
            self::Configuracion => 'Configuración',
            self::Sistema => 'Sistema',
        };
    }

    public function getSort(): int
    {
        return match ($this) {
            self::Documentos => 1,
            self::Items => 2,
            self::Inventario => 3,
            self::Configuracion => 4,
            self::Sistema => 5,
        };
    }
}
