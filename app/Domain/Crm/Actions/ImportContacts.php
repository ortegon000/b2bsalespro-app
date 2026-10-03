<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Facades\DB;

class ImportContacts
{
    public function __construct(private SaveContact $saveContact) {}

    /**
     * Importa las filas válidas leídas por ContactCsv::parse(). Las filas con error se omiten.
     * Al actualizar, las celdas vacías conservan el valor que ya tenía el contacto.
     *
     * @param  list<array{status: string, data: array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}, contact_id: ?int}>  $rows
     * @return array{created: int, updated: int}
     */
    public function handle(Company $company, array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0];

        DB::transaction(function () use ($company, $rows, &$result): void {
            foreach ($rows as $row) {
                if ($row['status'] === 'error') {
                    continue;
                }

                $contact = $row['contact_id'] ? Contact::findOrFail($row['contact_id']) : null;

                $this->saveContact->handle($company, array_filter($row['data'], fn ($value) => $value !== null), $contact);

                $result[$contact ? 'updated' : 'created']++;
            }
        });

        return $result;
    }
}
