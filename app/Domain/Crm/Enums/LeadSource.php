<?php

namespace App\Domain\Crm\Enums;

enum LeadSource: string
{
    case Form = 'form';
    case Whatsapp = 'whatsapp';
    case Landing = 'landing';
    case Website = 'website';
    case Import = 'import';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Form => 'Formulario',
            self::Whatsapp => 'WhatsApp',
            self::Landing => 'Landing',
            self::Website => 'Sitio web',
            self::Import => 'Importación',
            self::Manual => 'Manual',
        };
    }
}
