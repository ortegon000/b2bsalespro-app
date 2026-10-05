<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Subscription;

class PauseSubscription
{
    /**
     * Mientras está pausada, sus envíos programados no salen.
     */
    public function handle(Subscription $subscription): Subscription
    {
        if ($subscription->status === SubscriptionStatus::Active) {
            $subscription->update(['status' => SubscriptionStatus::Paused]);
        }

        return $subscription;
    }
}
