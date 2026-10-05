<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateReinforcement
{
    public function __construct(private ScheduleSubscription $scheduleSubscription) {}

    /**
     * Activa el refuerzo del curso: programa la secuencia para todos los inscritos
     * que puedan recibir correo, empezando en la fecha indicada (en la zona del negocio).
     *
     * @return int Cantidad de contactos programados
     *
     * @throws ValidationException
     */
    public function handle(Course $course, string $startsOn): int
    {
        $start = CarbonImmutable::parse($startsOn, config('crm.timezone'))->startOfDay();

        if ($course->isReinforcementActive()) {
            $this->fail('El refuerzo de este curso ya está activo.');
        }

        if ($course->sequence === null || ! $course->sequence->isReady()) {
            $this->fail('La secuencia no está lista: todos sus días necesitan una plantilla de Brevo.');
        }

        if ($start->lt(CarbonImmutable::now(config('crm.timezone'))->startOfDay())) {
            $this->fail('La fecha de inicio no puede ser anterior a hoy.');
        }

        $recipients = $course->contacts()->get()->filter->canReceiveEmail();

        if ($recipients->isEmpty()) {
            $this->fail('No hay contactos inscritos que puedan recibir correos.');
        }

        DB::transaction(function () use ($course, $start, $recipients): void {
            $course->update([
                'reinforcement_starts_on' => $start->toDateString(),
                'reinforcement_activated_at' => now(),
            ]);

            foreach ($recipients as $contact) {
                $this->scheduleSubscription->handle($course, $contact);
            }
        });

        return $recipients->count();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['startsOn' => $message]);
    }
}
