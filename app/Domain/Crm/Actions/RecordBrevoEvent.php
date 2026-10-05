<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Contact;

class RecordBrevoEvent
{
    /**
     * Eventos de Brevo que dejan de permitir envíos al correo y la baja que representan.
     *
     * @var array<string, 'unsubscribed'|'bounced'>
     */
    private const array BLOCKING_EVENTS = [
        'unsubscribed' => 'unsubscribed',
        'spam' => 'unsubscribed',
        'hard_bounce' => 'bounced',
        'blocked' => 'bounced',
        'invalid_email' => 'bounced',
    ];

    /**
     * Marca al contacto como baja o rebotado y cancela sus envíos pendientes.
     * Los demás eventos (entregado, abierto, soft bounce…) se ignoran.
     *
     * @return bool Si el evento afectó a un contacto
     */
    public function handle(string $event, string $email): bool
    {
        $reason = self::BLOCKING_EVENTS[$event] ?? null;
        $contact = Contact::where('email', $email)->first();

        if ($reason === null || $contact === null) {
            return false;
        }

        $contact->update($reason === 'unsubscribed' ? ['unsubscribed_at' => now()] : ['bounced_at' => now()]);

        $contact->load('subscriptions');

        foreach ($contact->subscriptions as $subscription) {
            $subscription->update([
                'status' => $reason === 'unsubscribed' ? SubscriptionStatus::Unsubscribed : SubscriptionStatus::Bounced,
            ]);

            $subscription->sends()
                ->whereIn('status', [SendStatus::Pending, SendStatus::Queued, SendStatus::Skipped])
                ->update(['status' => SendStatus::Cancelled]);
        }

        return true;
    }
}
