<?php

namespace App\Domain\Crm\Enums;

enum CourseModality: string
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::Online => 'Online',
        };
    }
}
