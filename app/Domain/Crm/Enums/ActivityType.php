<?php

namespace App\Domain\Crm\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Message = 'message';
    case Zoom = 'zoom';
    case Diagnosis = 'diagnosis';
    case Proposal = 'proposal';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Llamada',
            self::Message => 'Mensaje',
            self::Zoom => 'Zoom',
            self::Diagnosis => 'Diagnóstico',
            self::Proposal => 'Propuesta',
            self::Note => 'Nota',
        };
    }
}
