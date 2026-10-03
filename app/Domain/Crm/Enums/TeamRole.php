<?php

namespace App\Domain\Crm\Enums;

enum TeamRole: string
{
    case Admin = 'admin';
    case Seller = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Seller => 'Vendedor',
        };
    }
}
