<?php

namespace App\Domain\Crm\Jobs;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\SequenceStep;
use App\Domain\Crm\Services\BrevoClient;
use App\Domain\Crm\Services\ReinforcementEmail;
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

    public function handle(BrevoClient $brevo, ReinforcementEmail $emails): void
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

        $day = $send->step->day;

        if (! $emails->exists($day) && $send->step->brevo_template_id === null) {
            $send->update(['status' => SendStatus::Failed, 'error' => "El día {$day} no tiene correo (vista) ni plantilla de Brevo."]);

            return;
        }

        try {
            $messageId = $emails->exists($day)
                ? $this->sendView($brevo, $emails, $send)
                : $this->sendTemplate($brevo, $send);
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
     * Correo propio del día: se renderiza la vista Blade y se manda con su enlace de baja.
     */
    private function sendView(BrevoClient $brevo, ReinforcementEmail $emails, Send $send): string
    {
        $subscription = $send->subscription;
        $total = SequenceStep::where('sequence_id', $send->step->sequence_id)->count();
        $mail = $emails->render($send->step->day, $emails->dataFor($subscription, $total));
        $unsubscribeUrl = $emails->unsubscribeUrl($subscription);

        return $brevo->sendHtml(
            $mail['subject'],
            $mail['html'],
            $subscription->contact->email,
            $subscription->contact->name,
            ['crm-refuerzo', 'dia-'.$send->step->day],
            ['List-Unsubscribe' => "<{$unsubscribeUrl}>", 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'],
        );
    }

    /**
     * Día sin vista: se manda la plantilla de Brevo (transición).
     */
    private function sendTemplate(BrevoClient $brevo, Send $send): string
    {
        $subscription = $send->subscription;

        return $brevo->sendTemplate((int) $send->step->brevo_template_id, $subscription->contact->email, $subscription->contact->name, [
            'NOMBRE' => Str::before($subscription->contact->name, ' '),
            'NOMBRE_COMPLETO' => $subscription->contact->name,
            'EMPRESA' => $subscription->course->company->name,
            'CURSO' => $subscription->course->title,
            'DIA' => $send->step->day,
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
