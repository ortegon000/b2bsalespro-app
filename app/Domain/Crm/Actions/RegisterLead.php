<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use Illuminate\Support\Facades\DB;

class RegisterLead
{
    /**
     * Registra un prospecto que llega de un formulario, WhatsApp, landing o el sitio.
     * Si el email ya existe no duplica nada: agrega el mensaje como actividad de su empresa.
     *
     * @param  array<string, mixed>  $data  Campos validados por StoreLeadRequest: name, email, company, phone, job_title, message
     * @return array{company: Company, contact: Contact, duplicate: bool}
     */
    public function handle(array $data, LeadSource $source): array
    {
        return DB::transaction(function () use ($data, $source): array {
            $contact = Contact::with('company')->where('email', $data['email'])->first();
            $duplicate = $contact !== null;
            $company = $contact?->company;

            if ($company === null) {
                $company = Company::create([
                    'name' => $data['company'] ?? $data['name'],
                    'stage_id' => $this->firstOpenStage()->id,
                    'source' => $source,
                    'stage_changed_at' => now(),
                ]);

                if ($contact === null) {
                    $contact = $company->contacts()->create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'job_title' => $data['job_title'] ?? null,
                        'is_primary' => true,
                    ]);
                } else {
                    $contact->update(['company_id' => $company->id, 'is_primary' => true]);
                }
            }

            if (filled($data['message'] ?? null)) {
                $company->activities()->create([
                    'contact_id' => $contact->id,
                    'type' => ActivityType::Message,
                    'body' => $data['message'],
                    'occurred_at' => now(),
                ]);
            }

            return ['company' => $company, 'contact' => $contact, 'duplicate' => $duplicate];
        });
    }

    private function firstOpenStage(): Stage
    {
        return Stage::where('type', StageType::Open)->orderBy('position')->firstOrFail();
    }
}
