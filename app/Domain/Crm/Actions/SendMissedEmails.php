<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Subscription;

class SendMissedEmails
{
    /**
     * Reprograma los correos omitidos de la suscripción (inscripción tardía o pausa).
     * Salen en orden de día, uno por minuto, para no mandar todo de golpe.
     *
     * @return int Cantidad de correos reprogramados
     */
    public function handle(Subscription $subscription): int
    {
        if ($subscription->status !== SubscriptionStatus::Active || ! $subscription->contact->canReceiveEmail()) {
            return 0;
        }

        $missed = $subscription->sends()
            ->where('status', SendStatus::Skipped)
            ->join('crm_sequence_steps', 'crm_sequence_steps.id', '=', 'crm_sends.sequence_step_id')
            ->orderBy('crm_sequence_steps.day')
            ->select('crm_sends.*')
            ->get();

        foreach ($missed as $index => $send) {
            $send->update(['status' => SendStatus::Pending, 'scheduled_for' => now()->addMinutes($index)]);
        }

        return $missed->count();
    }
}
