<?php

namespace App\Http\Resources\Crm;

use App\Domain\Crm\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'company_id' => $this->id,
            'company' => $this->name,
            'stage' => $this->stage->slug,
            'source' => $this->source->value,
        ];
    }
}
