<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Plantilla y lectura del CSV para importar empresas con sus contactos.
 *
 * Cada fila es una empresa y, opcionalmente, uno de sus contactos; varias filas con la misma
 * empresa (sin importar mayúsculas ni acentos) agregan más contactos a esa empresa.
 *
 * @phpstan-type CompanyFields array{industry: ?string, website: ?string, notes: ?string, source: ?string, stage_id: ?int, owner_id: ?int, stage_label: ?string, owner_label: ?string}
 * @phpstan-type ParsedContact array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool, action: 'create'|'update', id: ?int}
 * @phpstan-type ParsedRow array{line: int, error: ?string, name: string, key: string, existingId: ?int, company: CompanyFields, contact: ?ParsedContact}
 * @phpstan-type Row array{line: int, status: 'create'|'update'|'error', message: ?string, company: array{name: string, key: string, action: 'create'|'update', id: ?int, first: bool, industry: ?string, website: ?string, notes: ?string, source: ?string, stage_id: ?int, owner_id: ?int, stage_label: ?string, owner_label: ?string}, contact: ?ParsedContact}
 */
class CompanyCsv
{
    /**
     * @var list<string>
     */
    private const array TEMPLATE_HEADERS = [
        'empresa', 'giro', 'sitio_web', 'etapa', 'responsable', 'origen', 'notas',
        'contacto', 'email', 'telefono', 'puesto', 'principal',
    ];

    /**
     * Encabezados aceptados (normalizados) y el campo al que corresponden.
     *
     * @var array<string, string>
     */
    private const array HEADER_ALIASES = [
        'empresa' => 'company', 'nombre de la empresa' => 'company', 'company' => 'company',
        'giro' => 'industry', 'industria' => 'industry', 'sector' => 'industry', 'industry' => 'industry',
        'sitio web' => 'website', 'sitio' => 'website', 'web' => 'website', 'website' => 'website', 'pagina web' => 'website',
        'etapa' => 'stage', 'stage' => 'stage',
        'responsable' => 'owner', 'vendedor' => 'owner', 'owner' => 'owner',
        'origen' => 'source', 'fuente' => 'source', 'source' => 'source',
        'notas' => 'notes', 'notes' => 'notes', 'observaciones' => 'notes',
        'contacto' => 'contact_name', 'nombre contacto' => 'contact_name', 'nombre del contacto' => 'contact_name', 'nombre' => 'contact_name',
        'email' => 'contact_email', 'correo' => 'contact_email', 'correo electronico' => 'contact_email', 'email contacto' => 'contact_email',
        'telefono' => 'contact_phone', 'celular' => 'contact_phone', 'phone' => 'contact_phone',
        'puesto' => 'contact_job_title', 'cargo' => 'contact_job_title',
        'principal' => 'contact_primary',
    ];

    public function __construct(private CsvReader $reader) {}

    public function template(): string
    {
        return $this->reader->write([
            self::TEMPLATE_HEADERS,
            ['Distribuidora Norte', 'Distribución', 'https://distribuidoranorte.example', 'Nuevo', '', 'Formulario', 'Interesados en capacitar a 12 vendedores', 'Rosa Díaz', 'rosa@distribuidoranorte.example', '55 1234 5678', 'Gerente comercial', 'si'],
            ['Distribuidora Norte', '', '', '', '', '', '', 'Jorge Mena', 'jorge@distribuidoranorte.example', '', 'Jefe de ventas', 'no'],
            ['Seguros Prisma', 'Servicios financieros', '', 'Diagnóstico', '', 'WhatsApp', '', '', '', '', '', ''],
        ]);
    }

    /**
     * Lee el CSV y clasifica cada fila contra lo que ya existe.
     *
     * Una fila es atómica: si algo falla (empresa o contacto) se omite completa. Los datos de
     * la empresa se toman de la primera celda no vacía entre las filas válidas de esa empresa.
     *
     * @return array{error: ?string, rows: list<Row>}
     */
    public function parse(string $contents, User $importer): array
    {
        $read = $this->reader->read(
            $contents,
            self::HEADER_ALIASES,
            ['company'],
            'El archivo debe tener al menos la columna "empresa". Descarga la plantilla para ver el formato.',
        );

        if ($read['error'] !== null) {
            return ['error' => $read['error'], 'rows' => []];
        }

        $lookups = $this->lookups($read['rows']);
        $seenEmails = [];
        $parsed = [];

        foreach ($read['rows'] as ['line' => $line, 'cells' => $cells]) {
            $parsed[] = $this->parseRow($line, $cells, $lookups, $seenEmails);
        }

        return ['error' => null, 'rows' => $this->assemble($parsed, $importer)];
    }

    /**
     * @param  list<Row>  $rows
     * @return array{companies_create: int, companies_update: int, contacts_create: int, contacts_update: int, errors: int}
     */
    public function summary(array $rows): array
    {
        $summary = ['companies_create' => 0, 'companies_update' => 0, 'contacts_create' => 0, 'contacts_update' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            if ($row['status'] === 'error') {
                $summary['errors']++;

                continue;
            }

            if ($row['company']['first']) {
                $summary[$row['company']['action'] === 'create' ? 'companies_create' : 'companies_update']++;
            }

            if ($row['contact'] !== null) {
                $summary[$row['contact']['action'] === 'create' ? 'contacts_create' : 'contacts_update']++;
            }
        }

        return $summary;
    }

    /**
     * Carga de una vez lo que hace falta para resolver todas las filas.
     *
     * @param  list<array{line: int, cells: array<string, string>}>  $rows
     * @return array{stages: array<string, Stage>, firstOpenStage: ?Stage, owners: array<string, int>, companies: array<string, int>, contacts: array<string, Contact>}
     */
    private function lookups(array $rows): array
    {
        $stages = [];
        $firstOpenStage = null;

        foreach (Stage::orderBy('position')->get() as $stage) {
            $stages[$this->reader->normalize($stage->name)] = $stage;
            $stages[$this->reader->normalize($stage->slug)] = $stage;
            $firstOpenStage ??= $stage->type === StageType::Open ? $stage : null;
        }

        $owners = [];

        foreach (TeamMember::with('user')->get() as $member) {
            $owners[Str::lower($member->user->email)] = $member->user_id;
        }

        $companies = [];

        foreach (Company::get(['id', 'name']) as $company) {
            $companies[$this->reader->normalize($company->name)] = $company->id;
        }

        $emails = array_values(array_filter(array_map(fn (array $row) => Str::lower($row['cells']['contact_email'] ?? ''), $rows)));
        $contacts = Contact::whereIn('email', $emails)->get()->keyBy(fn (Contact $contact) => Str::lower($contact->email))->all();

        return compact('stages', 'firstOpenStage', 'owners', 'companies', 'contacts');
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array{stages: array<string, Stage>, firstOpenStage: ?Stage, owners: array<string, int>, companies: array<string, int>, contacts: array<string, Contact>}  $lookups
     * @param  array<string, int>  $seenEmails
     * @return ParsedRow
     */
    private function parseRow(int $line, array $cells, array $lookups, array &$seenEmails): array
    {
        $name = $cells['company'] ?? '';
        $key = $this->reader->normalize($name);
        $existingId = $lookups['companies'][$key] ?? null;

        $error = $this->companyError($cells, $lookups, $stageId, $ownerId, $source);
        $stageName = ($cells['stage'] ?? '') !== '' ? ($lookups['stages'][$this->reader->normalize($cells['stage'])] ?? null)?->name : null;
        $contact = null;

        if ($error === null) {
            [$contact, $error] = $this->contactData($cells, $existingId, $lookups['contacts'], $line, $seenEmails);
        }

        return [
            'line' => $line,
            'error' => $error,
            'name' => $name,
            'key' => $key,
            'existingId' => $existingId,
            'contact' => $contact,
            'company' => [
                'industry' => ($cells['industry'] ?? '') ?: null,
                'website' => ($cells['website'] ?? '') ?: null,
                'notes' => ($cells['notes'] ?? '') ?: null,
                'source' => $source,
                'stage_id' => $stageId,
                'owner_id' => $ownerId,
                'stage_label' => $stageId !== null ? $stageName : null,
                'owner_label' => $ownerId !== null ? ($cells['owner'] ?? null) : null,
            ],
        ];
    }

    /**
     * Valida los datos de la empresa y resuelve etapa, responsable y origen por nombre.
     *
     * @param  array<string, string>  $cells
     * @param  array{stages: array<string, Stage>, firstOpenStage: ?Stage, owners: array<string, int>, companies: array<string, int>, contacts: array<string, Contact>}  $lookups
     */
    private function companyError(array $cells, array $lookups, ?int &$stageId, ?int &$ownerId, ?string &$source): ?string
    {
        $stageId = $ownerId = $source = null;

        $validator = Validator::make($cells, [
            'company' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'company.required' => 'Falta el nombre de la empresa.',
            'max' => 'El campo :attribute es demasiado largo.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        if (($cells['stage'] ?? '') !== '') {
            $stage = $lookups['stages'][$this->reader->normalize($cells['stage'])] ?? null;

            if ($stage === null) {
                return "La etapa \"{$cells['stage']}\" no existe.";
            }

            $stageId = $stage->id;
        }

        if (($cells['owner'] ?? '') !== '') {
            $ownerId = $lookups['owners'][Str::lower($cells['owner'])] ?? null;

            if ($ownerId === null) {
                return "El responsable \"{$cells['owner']}\" no es del equipo del CRM (usa su email).";
            }
        }

        if (($cells['source'] ?? '') !== '') {
            $source = $this->resolveSource($cells['source'])?->value;

            if ($source === null) {
                return "El origen \"{$cells['source']}\" no es válido.";
            }
        }

        return null;
    }

    private function resolveSource(string $text): ?LeadSource
    {
        $text = $this->reader->normalize($text);

        foreach (LeadSource::cases() as $case) {
            if ($text === $this->reader->normalize($case->value) || $text === $this->reader->normalize($case->label())) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array<string, Contact>  $existingContacts
     * @param  array<string, int>  $seenEmails
     * @return array{0: ?ParsedContact, 1: ?string}
     */
    private function contactData(array $cells, ?int $companyId, array $existingContacts, int $line, array &$seenEmails): array
    {
        $fields = ['contact_name', 'contact_email', 'contact_phone', 'contact_job_title', 'contact_primary'];

        if (collect($fields)->every(fn (string $field) => ($cells[$field] ?? '') === '')) {
            return [null, null];
        }

        $data = [
            'name' => $cells['contact_name'] ?? '',
            'email' => Str::lower($cells['contact_email'] ?? ''),
            'phone' => ($cells['contact_phone'] ?? '') ?: null,
            'job_title' => ($cells['contact_job_title'] ?? '') ?: null,
            'is_primary' => ($cells['contact_primary'] ?? '') === '' ? null : in_array($this->reader->normalize($cells['contact_primary']), ['si', 's', 'yes', 'y', '1', 'true', 'x'], true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Falta el nombre del contacto.',
            'email.required' => 'Falta el email del contacto.',
            'email.email' => 'El email del contacto no es válido.',
            'max' => 'El campo :attribute es demasiado largo.',
        ]);

        $error = $validator->fails() ? $validator->errors()->first() : null;
        $existing = $existingContacts[$data['email']] ?? null;

        if ($error === null && isset($seenEmails[$data['email']])) {
            $error = 'Email repetido en el archivo (fila '.$seenEmails[$data['email']].').';
        }

        if ($error === null && $existing?->company_id !== null && $existing->company_id !== $companyId) {
            $error = 'Ese email ya pertenece a otra empresa.';
        }

        if ($data['email'] !== '') {
            $seenEmails[$data['email']] ??= $line;
        }

        return [$error === null ? $data + ['action' => $existing ? 'update' : 'create', 'id' => $existing?->id] : null, $error];
    }

    /**
     * Junta los datos de cada empresa entre sus filas válidas y arma el resultado por fila.
     *
     * @param  list<ParsedRow>  $parsed
     * @return list<Row>
     */
    private function assemble(array $parsed, User $importer): array
    {
        $merged = [];

        foreach ($parsed as $row) {
            if ($row['error'] !== null) {
                continue;
            }

            $current = $merged[$row['key']] ?? null;
            $fields = $row['company'];

            $merged[$row['key']] = [
                'industry' => $current['industry'] ?? $fields['industry'],
                'website' => $current['website'] ?? $fields['website'],
                'notes' => $current['notes'] ?? $fields['notes'],
                'source' => $current['source'] ?? $fields['source'],
                'stage_id' => $current['stage_id'] ?? $fields['stage_id'],
                'owner_id' => $current['owner_id'] ?? $fields['owner_id'],
                'stage_label' => $current['stage_label'] ?? $fields['stage_label'],
                'owner_label' => $current['owner_label'] ?? $fields['owner_label'],
            ];
        }

        $started = [];
        $rows = [];

        foreach ($parsed as $row) {
            $first = $row['error'] === null && ! isset($started[$row['key']]);
            $started[$row['key']] = true;

            $company = ($merged[$row['key']] ?? $row['company']) + [
                'name' => $row['name'],
                'key' => $row['key'],
                'action' => $row['existingId'] !== null ? 'update' : 'create',
                'id' => $row['existingId'],
                'first' => $first,
            ];

            $rows[] = [
                'line' => $row['line'],
                'status' => $row['error'] !== null ? 'error' : $this->status($row, $first),
                'message' => $row['error'],
                'company' => $company,
                'contact' => $row['contact'],
            ];
        }

        return $rows;
    }

    /**
     * @param  ParsedRow  $row
     * @return 'create'|'update'
     */
    private function status(array $row, bool $first): string
    {
        $createsCompany = $first && $row['existingId'] === null;
        $createsContact = ($row['contact']['action'] ?? null) === 'create';

        return $createsCompany || $createsContact ? 'create' : 'update';
    }
}
