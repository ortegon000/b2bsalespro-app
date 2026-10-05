<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;

class EnrollContacts
{
    public function __construct(private ScheduleSubscription $scheduleSubscription) {}

    /**
     * Inscribe contactos de la empresa del curso. Si el refuerzo ya está activo, los programa
     * alineados al grupo (los días pasados quedan omitidos).
     *
     * @param  list<int>  $contactIds
     * @return int Cantidad de contactos inscritos
     */
    public function handle(Course $course, array $contactIds): int
    {
        $contacts = Contact::query()
            ->where('company_id', $course->company_id)
            ->whereKey($contactIds)
            ->get();

        $course->contacts()->syncWithoutDetaching($contacts->modelKeys());

        if ($course->isReinforcementActive()) {
            $course->load('sequence.steps');

            foreach ($contacts->filter->canReceiveEmail() as $contact) {
                $this->scheduleSubscription->handle($course, $contact);
            }
        }

        return $contacts->count();
    }
}
