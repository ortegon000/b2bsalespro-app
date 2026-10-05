<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Subscription;

class ScheduleSubscription
{
    /**
     * Crea la suscripción del contacto al refuerzo del curso y programa sus envíos.
     *
     * Cada correo sale en la fecha del grupo. Los días que ya pasaron (inscripción tardía)
     * quedan como `skipped`, para que se puedan enviar después con SendMissedEmails.
     * Es idempotente: no duplica suscripciones ni envíos.
     */
    public function handle(Course $course, Contact $contact): Subscription
    {
        $subscription = Subscription::firstOrCreate(['contact_id' => $contact->id, 'course_id' => $course->id]);

        $steps = $course->sequence === null ? collect() : $course->sequence->steps;

        foreach ($steps as $step) {
            $scheduledFor = $course->sendTimeForDay($step->day);

            Send::firstOrCreate(
                ['subscription_id' => $subscription->id, 'sequence_step_id' => $step->id],
                [
                    'scheduled_for' => $scheduledFor,
                    'status' => $scheduledFor->isPast() ? SendStatus::Skipped : SendStatus::Pending,
                ],
            );
        }

        return $subscription;
    }
}
