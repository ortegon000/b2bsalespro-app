<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Contact;

class RecordBrevoEvent
{
    public function __construct(private UnsubscribeContact $unsubscribeContact) {}

    /**
     * Eventos de Brevo que dejan de permitir envíos al correo y la baja que representan.
     * Las claves van normalizadas (ver normalize()): Brevo los escribe de varias formas
     * (`hard_bounce` / `hardBounce`, `invalid_email` / `invalid`, `unsubscribe` / `unsubscribed`).
     *
     * @var array<string, 'unsubscribed'|'bounced'>
     */
    private const array BLOCKING_EVENTS = [
        'unsubscribed' => 'unsubscribed',
        'unsubscribe' => 'unsubscribed',
        'spam' => 'unsubscribed',
        'hardbounce' => 'bounced',
        'blocked' => 'bounced',
        'invalidemail' => 'bounced',
        'invalid' => 'bounced',
    ];

    /**
     * Marca al contacto como baja o rebotado y cancela sus envíos pendientes.
     * Los demás eventos (entregado, abierto, soft bounce…) se ignoran.
     *
     * @return bool Si el evento afectó a un contacto
     */
    public function handle(string $event, string $email): bool
    {
        $reason = self::BLOCKING_EVENTS[$this->normalize($event)] ?? null;
        $contact = Contact::where('email', $email)->first();

        if ($reason === null || $contact === null) {
            return false;
        }

        $this->unsubscribeContact->handle(
            $contact,
            $reason === 'unsubscribed' ? SubscriptionStatus::Unsubscribed : SubscriptionStatus::Bounced,
        );

        return true;
    }

    /**
     * Minúsculas y sin guiones bajos: `hard_bounce` y `hardBounce` son el mismo evento.
     */
    private function normalize(string $event): string
    {
        return str_replace(['_', '-', ' '], '', mb_strtolower($event));
    }
}
