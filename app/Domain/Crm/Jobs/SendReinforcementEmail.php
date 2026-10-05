<?php

namespace App\Domain\Crm\Jobs;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Services\BrevoClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class SendReinforcementEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $sendId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(BrevoClient $brevo): void
    {
        $send = Send::with(['subscription.contact', 'subscription.course.company', 'step'])->find($this->sendId);

        // Solo se envía lo que el despachador reclamó: evita reenviar si el job se repite.
        if ($send === null || $send->status !== SendStatus::Queued) {
            return;
        }

        $subscription = $send->subscription;
        $contact = $subscription->contact;

        if ($subscription->status !== SubscriptionStatus::Active || ! $contact->canReceiveEmail()) {
            $send->update(['status' => SendStatus::Cancelled]);

            return;
        }

        if ($send->step->brevo_template_id === null) {
            $send->update(['status' => SendStatus::Failed, 'error' => 'El paso no tiene plantilla de Brevo.']);

            return;
        }

        try {
            $messageId = $brevo->sendTemplate($send->step->brevo_template_id, $contact->email, $contact->name, [
                'NOMBRE' => Str::before($contact->name, ' '),
                'NOMBRE_COMPLETO' => $contact->name,
                'EMPRESA' => $subscription->course->company->name,
                'CURSO' => $subscription->course->title,
                'DIA' => $send->step->day,
            ]);
        } catch (BrevoException $exception) {
            if ($exception->retryable) {
                throw $exception;
            }

            $send->update(['status' => SendStatus::Failed, 'error' => $exception->getMessage()]);

            return;
        }

        $send->update([
            'status' => SendStatus::Sent,
            'sent_at' => now(),
            'brevo_message_id' => $messageId,
            'error' => null,
        ]);
    }

    /**
     * Se agotaron los reintentos: queda registrado el motivo para poder reintentar desde el CRM.
     */
    public function failed(Throwable $exception): void
    {
        Send::whereKey($this->sendId)
            ->where('status', SendStatus::Queued)
            ->update(['status' => SendStatus::Failed, 'error' => $exception->getMessage()]);
    }
}
