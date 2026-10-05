<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Subscription;

class RetryFailedSends
{
    /**
     * Vuelve a programar de inmediato los envíos que fallaron.
     *
     * @return int Cantidad de envíos reprogramados
     */
    public function handle(Subscription $subscription): int
    {
        return $subscription->sends()
            ->where('status', SendStatus::Failed)
            ->update(['status' => SendStatus::Pending, 'scheduled_for' => now(), 'error' => null]);
    }
}
