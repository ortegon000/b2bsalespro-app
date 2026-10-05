<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Contact;

class UnsubscribeContact
{
    /**
     * Deja de enviarle correos al contacto: lo marca como dado de baja (o con el correo rebotado),
     * pone todas sus suscripciones en ese estado y cancela sus envíos que no han salido.
     * Es idempotente: repetirlo no cambia nada.
     *
     * @param  SubscriptionStatus  $reason  `Unsubscribed` o `Bounced`
     */
    public function handle(Contact $contact, SubscriptionStatus $reason = SubscriptionStatus::Unsubscribed): void
    {
        $column = $reason === SubscriptionStatus::Bounced ? 'bounced_at' : 'unsubscribed_at';

        $contact->update([$column => $contact->{$column} ?? now()]);

        foreach ($contact->subscriptions as $subscription) {
            $subscription->update(['status' => $reason]);

            $subscription->sends()
                ->whereIn('status', [SendStatus::Pending, SendStatus::Queued, SendStatus::Skipped])
                ->update(['status' => SendStatus::Cancelled]);
        }
    }
}
