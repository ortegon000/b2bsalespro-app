<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Services\BrevoClient;
use App\Domain\Crm\Services\ReinforcementEmail;
use App\Models\User;

class SendTestEmail
{
    public function __construct(private BrevoClient $brevo, private ReinforcementEmail $emails) {}

    /**
     * Manda el correo de un día al propio usuario, con datos de ejemplo y «[PRUEBA]» en el asunto,
     * para revisarlo en una bandeja real. No crea suscripciones ni envíos.
     *
     * @return string Asunto con el que se envió
     *
     * @throws BrevoException
     */
    public function handle(Sequence $sequence, int $day, User $to): string
    {
        $mail = $this->emails->render($day, $this->emails->sampleData($sequence->steps()->count(), $to->name));
        $subject = '[PRUEBA] '.$mail['subject'];

        $this->brevo->sendHtml($subject, $mail['html'], $to->email, $to->name, ['crm-prueba']);

        return $subject;
    }
}
