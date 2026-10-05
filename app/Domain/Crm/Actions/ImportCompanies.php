<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Services\CompanyCsv;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Row from CompanyCsv
 */
class ImportCompanies
{
    public function __construct(private SaveContact $saveContact, private MoveCompanyToStage $moveCompanyToStage) {}

    /**
     * Importa las filas válidas leídas por CompanyCsv::parse(); las filas con error se omiten.
     *
     * Las empresas nuevas empiezan en la etapa indicada (o en la primera abierta) y quedan a cargo
     * del responsable indicado (o de quien importa). En las existentes solo se cambia lo que el
     * archivo trae lleno; una etapa distinta mueve a la empresa y reinicia su antigüedad.
     *
     * @param  list<Row>  $rows  Filas de CompanyCsv::parse()
     * @return array{companies_created: int, companies_updated: int, contacts_created: int, contacts_updated: int}
     */
    public function handle(array $rows, User $importer): array
    {
        $result = ['companies_created' => 0, 'companies_updated' => 0, 'contacts_created' => 0, 'contacts_updated' => 0];

        DB::transaction(function () use ($rows, $importer, &$result): void {
            $companies = [];

            foreach ($rows as $row) {
                if ($row['status'] === 'error') {
                    continue;
                }

                $data = $row['company'];

                if (! isset($companies[$data['key']])) {
                    $companies[$data['key']] = $this->company($data, $importer, $result);
                }

                if ($row['contact'] === null) {
                    continue;
                }

                $contact = $row['contact'];
                $existing = $contact['id'] !== null ? Contact::findOrFail($contact['id']) : null;

                $this->saveContact->handle($companies[$data['key']], array_filter([
                    'name' => $contact['name'],
                    'email' => $contact['email'],
                    'phone' => $contact['phone'],
                    'job_title' => $contact['job_title'],
                    'is_primary' => $contact['is_primary'],
                ], fn ($value) => $value !== null), $existing);

                $result[$existing ? 'contacts_updated' : 'contacts_created']++;
            }
        });

        return $result;
    }

    /**
     * @param  Row['company']  $data
     * @param  array{companies_created: int, companies_updated: int, contacts_created: int, contacts_updated: int}  $result
     */
    private function company(array $data, User $importer, array &$result): Company
    {
        $attributes = array_filter([
            'industry' => $data['industry'],
            'website' => $data['website'],
            'notes' => $data['notes'],
            'source' => $data['source'],
            'owner_id' => $data['owner_id'],
        ], fn ($value) => $value !== null);

        if ($data['id'] !== null) {
            $company = Company::findOrFail($data['id']);
            $company->update($attributes);

            if ($data['stage_id'] !== null) {
                $this->moveCompanyToStage->handle($company, Stage::findOrFail($data['stage_id']));
            }

            $result['companies_updated']++;

            return $company;
        }

        $result['companies_created']++;

        return Company::create($attributes + [
            'name' => $data['name'],
            'stage_id' => $data['stage_id'] ?? Stage::where('type', StageType::Open)->orderBy('position')->firstOrFail()->id,
            'owner_id' => $importer->id,
            'source' => LeadSource::Import,
            'stage_changed_at' => now(),
        ]);
    }
}
