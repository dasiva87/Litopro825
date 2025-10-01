<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case AVAILABLE = 'available';
    case IN_CART = 'in_cart';
    case ORDERED = 'ordered';
    case RECEIVED = 'received';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Disponible',
            self::IN_CART => 'En Carrito',
            self::ORDERED => 'Ordenado',
            self::RECEIVED => 'Recibido',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AVAILABLE => 'success',
            self::IN_CART => 'warning',
            self::ORDERED => 'info',
            self::RECEIVED => 'primary',
        };
    }
}