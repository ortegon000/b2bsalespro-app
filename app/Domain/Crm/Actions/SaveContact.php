<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Facades\DB;

class SaveContact
{
    /**
     * Crea o actualiza un contacto de la empresa, garantizando un solo contacto
     * principal: el primero que se agrega lo es, y marcar otro le quita la marca al anterior.
     *
     * @param  array{name: string, email: string, phone?: ?string, job_title?: ?string, is_primary?: bool}  $data
     */
    public function handle(Company $company, array $data, ?Contact $contact = null): Contact
    {
        return DB::transaction(function () use ($company, $data, $contact): Contact {
            $contact ??= new Contact(['company_id' => $company->id]);

            $isFirst = ! $company->contacts()->whereKeyNot($contact->getKey())->exists();
            $data['is_primary'] = $isFirst || ($data['is_primary'] ?? false);

            if ($data['is_primary']) {
                $company->contacts()->whereKeyNot($contact->getKey())->update(['is_primary' => false]);
            }

            $contact->fill($data)->save();

            return $contact;
        });
    }
}
