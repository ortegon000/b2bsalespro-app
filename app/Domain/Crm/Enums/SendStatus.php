<?php

namespace App\Domain\Crm\Enums;

enum SendStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Programado',
            self::Queued => 'En cola',
            self::Sent => 'Enviado',
            self::Failed => 'Falló',
            self::Skipped => 'Omitido',
            self::Cancelled => 'Cancelado',
        };
    }
}
