<?php

namespace App\Domain\Crm\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Paused => 'Pausada',
            self::Unsubscribed => 'Dio de baja',
            self::Bounced => 'Correo rebotado',
        };
    }
}
