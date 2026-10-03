<?php

namespace App\Domain\Crm\Enums;

enum StageType: string
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Won => 'Ganada',
            self::Lost => 'Perdida',
        };
    }
}
