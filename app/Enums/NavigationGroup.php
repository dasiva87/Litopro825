<?php

namespace App\Enums;

enum NavigationGroup
{
    case Cotizaciones;
    case Inventario;
    case Configuracion;
    case Sistema;
    case Usuarios;
    
    public function getLabel(): string
    {
        return match($this) {
            self::Cotizaciones => 'Cotizaciones',
            self::Inventario => 'Inventario',
            self::Configuracion => 'Configuración',
            self::Sistema => 'Sistema',
            self::Usuarios => 'Usuarios',
        };
    }
}