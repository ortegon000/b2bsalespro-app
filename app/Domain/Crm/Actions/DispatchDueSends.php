<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Jobs\SendReinforcementEmail;
use App\Domain\Crm\Models\Send;

class DispatchDueSends
{
    public const int BATCH_SIZE = 200;

    /**
     * Encola los envíos vencidos de suscripciones activas. Cada envío se reclama de forma
     * atómica (pending → queued) para que dos ejecuciones simultáneas no lo encolen dos veces.
     *
     * @return int Cantidad de envíos encolados
     */
    public function handle(): int
    {
        $dispatched = 0;

        $due = Send::query()
            ->where('status', SendStatus::Pending)
            ->where('scheduled_for', '<=', now())
            ->whereHas('subscription', fn ($query) => $query->where('status', SubscriptionStatus::Active))
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->limit(self::BATCH_SIZE)
            ->get();

        foreach ($due as $send) {
            $claimed = Send::whereKey($send->id)
                ->where('status', SendStatus::Pending)
                ->update(['status' => SendStatus::Queued]);

            if ($claimed) {
                SendReinforcementEmail::dispatch($send->id);
                $dispatched++;
            }
        }

        return $dispatched;
    }
}
