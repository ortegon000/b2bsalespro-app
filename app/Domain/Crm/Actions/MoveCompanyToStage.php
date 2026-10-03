<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;

class MoveCompanyToStage
{
    public function handle(Company $company, Stage $stage): Company
    {
        if ($company->stage_id === $stage->id) {
            return $company;
        }

        $company->update([
            'stage_id' => $stage->id,
            'stage_changed_at' => now(),
            'lost_reason' => $stage->type === StageType::Lost ? $company->lost_reason : null,
        ]);

        return $company;
    }
}
