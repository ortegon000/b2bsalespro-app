<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Plantilla y lectura del CSV para importar los contactos de una empresa.
 */
class ContactCsv
{
    public function __construct(private CsvReader $reader) {}

    /**
     * Columnas de la plantilla, en el orden en que se descargan.
     *
     * @var list<string>
     */
    private const array TEMPLATE_HEADERS = ['nombre', 'email', 'telefono', 'puesto', 'principal'];

    /**
     * Encabezados aceptados (sin acentos ni mayúsculas) y el campo al que corresponden.
     *
     * @var array<string, string>
     */
    private const array HEADER_ALIASES = [
        'nombre' => 'name', 'name' => 'name',
        'email' => 'email', 'correo' => 'email', 'correo electronico' => 'email',
        'telefono' => 'phone', 'celular' => 'phone', 'phone' => 'phone',
        'puesto' => 'job_title', 'cargo' => 'job_title', 'job title' => 'job_title',
        'principal' => 'is_primary', 'is primary' => 'is_primary',
    ];

    public function template(): string
    {
        $rows = [
            self::TEMPLATE_HEADERS,
            ['María López', 'maria.lopez@empresa.com', '55 1234 5678', 'Vendedora', 'no'],
            ['Carlos Pérez', 'carlos.perez@empresa.com', '', 'Gerente de ventas', 'si'],
        ];

        return $this->reader->write($rows);
    }

    /**
     * Lee el CSV y clasifica cada fila contra los contactos existentes.
     *
     * Cada fila queda como `create`, `update` (mismo email en esta empresa o sin empresa)
     * o `error` (dato inválido, repetido en el archivo o email de otra empresa).
     *
     * @return array{
     *     error: ?string,
     *     rows: list<array{line: int, status: 'create'|'update'|'error', data: array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}, message: ?string, contact_id: ?int}>
     * }
     */
    public function parse(string $contents, Company $company): array
    {
        $read = $this->reader->read(
            $contents,
            self::HEADER_ALIASES,
            ['name', 'email'],
            'El archivo debe tener al menos las columnas "nombre" y "email". Descarga la plantilla para ver el formato.',
        );

        if ($read['error'] !== null) {
            return ['error' => $read['error'], 'rows' => []];
        }

        $raw = array_map(fn (array $row) => ['line' => $row['line'], 'data' => $this->rowData($row['cells'])], $read['rows']);

        return ['error' => null, 'rows' => $this->classify($raw, $company)];
    }

    /**
     * @param  array<string, string>  $cells
     * @return array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}
     */
    private function rowData(array $cells): array
    {
        $isPrimary = $cells['is_primary'] ?? '';

        return [
            'name' => $cells['name'] ?? '',
            'email' => Str::lower($cells['email'] ?? ''),
            'phone' => ($cells['phone'] ?? '') ?: null,
            'job_title' => ($cells['job_title'] ?? '') ?: null,
            'is_primary' => $isPrimary === '' ? null : in_array($this->reader->normalize($isPrimary), ['si', 's', 'yes', 'y', '1', 'true', 'x'], true),
        ];
    }

    /**
     * @param  list<array{line: int, data: array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}}>  $raw
     * @return list<array{line: int, status: 'create'|'update'|'error', data: array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}, message: ?string, contact_id: ?int}>
     */
    private function classify(array $raw, Company $company): array
    {
        $existing = Contact::whereIn('email', array_column(array_column($raw, 'data'), 'email'))->get()->keyBy(fn (Contact $c) => Str::lower($c->email));
        $seen = [];
        $rows = [];

        foreach ($raw as ['line' => $line, 'data' => $data]) {
            $message = $this->validationMessage($data);
            $contact = $existing->get($data['email']);

            if ($message === null && isset($seen[$data['email']])) {
                $message = 'Email repetido en el archivo (fila '.$seen[$data['email']].').';
            }

            if ($message === null && $contact?->company_id !== null && $contact->company_id !== $company->id) {
                $message = 'Ese email ya pertenece a otra empresa.';
            }

            $seen[$data['email']] ??= $line;

            $rows[] = [
                'line' => $line,
                'status' => $message !== null ? 'error' : ($contact ? 'update' : 'create'),
                'data' => $data,
                'message' => $message,
                'contact_id' => $message === null ? $contact?->id : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}  $data
     */
    private function validationMessage(array $data): ?string
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Falta el nombre.',
            'email.required' => 'Falta el email.',
            'email.email' => 'El email no es válido.',
            'max' => 'El campo :attribute es demasiado largo.',
        ]);

        return $validator->fails() ? $validator->errors()->first() : null;
    }
}
