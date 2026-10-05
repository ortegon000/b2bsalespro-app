<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Stage;

class MarkCourseDelivered
{
    public function __construct(private MoveCompanyToStage $moveCompanyToStage) {}

    /**
     * Marca el curso como impartido y, si la empresa sigue en una etapa abierta,
     * la pasa a la primera etapa ganada del pipeline.
     */
    public function handle(Course $course): Course
    {
        $course->update(['delivered_at' => $course->delivered_at ?? now()]);

        $company = $course->company;

        if ($company->stage->type === StageType::Open) {
            $won = Stage::where('type', StageType::Won)->orderBy('position')->first();

            if ($won) {
                $this->moveCompanyToStage->handle($company, $won);
            }
        }

        return $course;
    }
}
