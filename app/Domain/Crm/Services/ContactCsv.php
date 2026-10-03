<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SplTempFileObject;

/**
 * Plantilla y lectura del CSV para importar los contactos de una empresa.
 */
class ContactCsv
{
    public const int MAX_ROWS = 500;

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
        'puesto' => 'job_title', 'cargo' => 'job_title', 'job_title' => 'job_title',
        'principal' => 'is_primary', 'is_primary' => 'is_primary',
    ];

    public function template(): string
    {
        $rows = [
            self::TEMPLATE_HEADERS,
            ['María López', 'maria.lopez@empresa.com', '55 1234 5678', 'Vendedora', 'no'],
            ['Carlos Pérez', 'carlos.perez@empresa.com', '', 'Gerente de ventas', 'si'],
        ];

        $file = new SplTempFileObject;

        foreach ($rows as $row) {
            $file->fputcsv($row, ',', '"', '');
        }

        $file->rewind();
        $csv = '';

        foreach ($file as $line) {
            $csv .= $line;
        }

        // BOM para que Excel abra los acentos correctamente.
        return "\xEF\xBB\xBF".$csv;
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
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $file = new SplTempFileObject;
        $file->fwrite($contents);
        $file->rewind();
        $file->setCsvControl($delimiter, '"', '');

        $columns = $this->mapHeaders($this->cleanRecord($file->fgetcsv()) ?? []);

        if (! in_array('name', $columns, true) || ! in_array('email', $columns, true)) {
            return ['error' => 'El archivo debe tener al menos las columnas "nombre" y "email". Descarga la plantilla para ver el formato.', 'rows' => []];
        }

        $raw = [];
        $line = 1;

        while (! $file->eof()) {
            $values = $this->cleanRecord($file->fgetcsv());
            $line++;

            if ($values === null) {
                continue;
            }

            if (count($raw) >= self::MAX_ROWS) {
                return ['error' => 'El archivo tiene más de '.self::MAX_ROWS.' filas. Divídelo en varios archivos.', 'rows' => []];
            }

            $raw[] = ['line' => $line, 'data' => $this->rowData($columns, $values)];
        }

        return ['error' => null, 'rows' => $this->classify($raw, $company)];
    }

    /**
     * @param  list<?string>  $headers
     * @return list<?string>
     */
    private function mapHeaders(array $headers): array
    {
        return array_map(
            fn (?string $header): ?string => self::HEADER_ALIASES[Str::of((string) $header)->ascii()->lower()->trim()->toString()] ?? null,
            $headers,
        );
    }

    /**
     * @param  list<?string>  $columns
     * @param  list<?string>  $values
     * @return array{name: string, email: string, phone: ?string, job_title: ?string, is_primary: ?bool}
     */
    private function rowData(array $columns, array $values): array
    {
        $name = '';
        $email = '';
        $phone = null;
        $jobTitle = null;
        $isPrimary = null;

        foreach ($columns as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));

            if ($field === null || $value === '') {
                continue;
            }

            match ($field) {
                'name' => $name = $value,
                'email' => $email = Str::lower($value),
                'phone' => $phone = $value,
                'job_title' => $jobTitle = $value,
                'is_primary' => $isPrimary = in_array(Str::of($value)->ascii()->lower()->toString(), ['si', 's', 'yes', 'y', '1', 'true', 'x'], true),
                default => null,
            };
        }

        return ['name' => $name, 'email' => $email, 'phone' => $phone, 'job_title' => $jobTitle, 'is_primary' => $isPrimary];
    }

    /**
     * Devuelve el registro como lista de celdas, o null si la línea está vacía.
     *
     * @param  array<int, mixed>|false  $record
     * @return list<?string>|null
     */
    private function cleanRecord(array|false $record): ?array
    {
        if ($record === false) {
            return null;
        }

        $cells = array_map(fn ($cell) => is_string($cell) ? $cell : null, array_values($record));

        return count(array_filter($cells, fn (?string $cell) => trim((string) $cell) !== '')) === 0 ? null : $cells;
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
