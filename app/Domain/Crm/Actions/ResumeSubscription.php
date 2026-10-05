<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Subscription;

class ResumeSubscription
{
    /**
     * Reanuda la suscripción. Los envíos que vencieron durante la pausa no salen todos de golpe:
     * quedan como `skipped` y se pueden enviar con SendMissedEmails.
     */
    public function handle(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Paused) {
            return $subscription;
        }

        $subscription->update(['status' => SubscriptionStatus::Active]);

        $subscription->sends()
            ->where('status', SendStatus::Pending)
            ->where('scheduled_for', '<=', now())
            ->update(['status' => SendStatus::Skipped]);

        return $subscription;
    }
}
