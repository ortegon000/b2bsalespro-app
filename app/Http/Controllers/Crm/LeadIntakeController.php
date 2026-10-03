<?php

namespace App\Http\Controllers\Crm;

use App\Domain\Crm\Actions\RegisterLead;
use App\Domain\Crm\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreLeadRequest;
use App\Http\Resources\Crm\LeadResource;
use Illuminate\Http\JsonResponse;

class LeadIntakeController extends Controller
{
    public function __invoke(StoreLeadRequest $request, RegisterLead $registerLead): JsonResponse
    {
        $result = $registerLead->handle(
            $request->safe()->except('source'),
            LeadSource::from($request->validated('source')),
        );

        return (new LeadResource($result['company']->load('stage')))
            ->additional(['duplicate' => $result['duplicate']])
            ->response()
            ->setStatusCode($result['duplicate'] ? 200 : 201);
    }
}
