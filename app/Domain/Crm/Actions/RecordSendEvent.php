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
     * - `unique_opened`: marca la primera apertura, sin contarla (Brevo manda también `opened`).
     * - `opened`: cuenta una apertura y marca la primera.
     * - `click`: cuenta un clic; un clic implica que el correo se abrió.
     *
     * @return bool Si el evento se aplicó a un envío
     */
    public function handle(string $event, ?string $messageId, ?int $timestamp = null): bool
    {
        if (! in_array($event, ['delivered', 'unique_opened', 'opened', 'click'], true) || blank($messageId)) {
            return false;
        }

        $send = $this->find($messageId);

        if ($send === null) {
            return false;
        }

        $at = $timestamp ? Carbon::createFromTimestampUTC($timestamp) : now();

        $changes = match ($event) {
            'delivered' => ['delivered_at' => $send->delivered_at ?? $at],
            'unique_opened' => ['opened_at' => $send->opened_at ?? $at],
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
}
