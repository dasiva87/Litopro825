<?php

namespace App\Enums;

enum NavigationGroup: string
{
    case Documentos = 'documentos';
    case Contactos = 'contactos';
    case Items = 'items';
    case Inventario = 'inventario';
    case Configuracion = 'configuracion';
    case Sistema = 'sistema';

    public function getLabel(): string
    {
        return match ($this) {
            self::Documentos => 'Documentos',
            self::Contactos => 'Contactos',
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
            self::Contactos => 2,
            self::Items => 3,
            self::Inventario => 4,
            self::Configuracion => 5,
            self::Sistema => 6,
        };
    }
}
