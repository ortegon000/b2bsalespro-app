<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Send;
use Illuminate\Support\Carbon;

class RecordSendEvent
{
    /**
     * Registra un evento de entrega de Brevo (entregado, abierto, clic) en el envío al que pertenece,
     * identificado por el `message-id` que Brevo devolvió al enviarlo. Los demás eventos se ignoran.
     *
     * - `delivered`: marca la entrega.
     * - `unique_opened` / `uniqueOpened`: marca la primera apertura, sin contarla (Brevo manda también `opened`).
     * - `opened`: cuenta una apertura y marca la primera.
     * - `click`: cuenta un clic; un clic implica que el correo se abrió.
     *
     * Las aperturas «proxy» (`proxyOpen`, `uniqueProxyOpen`: Apple Mail Privacy Protection abre los
     * correos por su cuenta) se ignoran a propósito para no inflar las aperturas.
     *
     * Brevo escribe los nombres de varias formas (`unique_opened` / `uniqueOpened`).
     *
     * @return bool Si el evento se aplicó a un envío
     */
    public function handle(string $event, ?string $messageId, ?int $timestamp = null): bool
    {
        $event = $this->normalize($event);

        if (! in_array($event, ['delivered', 'uniqueopened', 'opened', 'click'], true) || blank($messageId)) {
            return false;
        }

        $send = $this->find($messageId);

        if ($send === null) {
            return false;
        }

        $at = $timestamp ? Carbon::createFromTimestampUTC($timestamp) : now();

        $changes = match ($event) {
            'delivered' => ['delivered_at' => $send->delivered_at ?? $at],
            'uniqueopened' => ['opened_at' => $send->opened_at ?? $at],
            'opened' => ['opened_at' => $send->opened_at ?? $at, 'opens_count' => $send->opens_count + 1],
            default => ['opened_at' => $send->opened_at ?? $at, 'clicked_at' => $send->clicked_at ?? $at, 'clicks_count' => $send->clicks_count + 1],
        };

        $send->update($changes);

        return true;
    }

    /**
     * Brevo entrega el id con o sin los signos < >, según el evento.
     */
    private function find(string $messageId): ?Send
    {
        $bare = trim($messageId, '<> ');

        return Send::whereIn('brevo_message_id', [$messageId, $bare, "<{$bare}>"])->first();
    }

    private function normalize(string $event): string
    {
        return str_replace(['_', '-', ' '], '', mb_strtolower($event));
    }
}
