<?php

namespace App\Enums;

enum NavigationGroup: string
{
    case Cotizaciones = 'cotizaciones';
    case Catalogos = 'catalogos';
    case Configuracion = 'configuracion';
    case Sistema = 'sistema';
    case Usuarios = 'usuarios';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cotizaciones => 'Cotizaciones',
            self::Catalogos => 'Catálogos',
            self::Configuracion => 'Configuración',
            self::Sistema => 'Sistema',
            self::Usuarios => 'Usuarios',
        };
    }
}
