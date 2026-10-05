<?php

namespace App\Domain\Crm\Services;

use Illuminate\Support\Str;
use SplTempFileObject;

/**
 * Lectura y escritura de CSV para las importaciones del CRM: quita el BOM, convierte
 * Windows-1252 a UTF-8, detecta coma o punto y coma, y mapea encabezados con alias.
 */
class CsvReader
{
    public const int MAX_ROWS = 500;

    /**
     * Lee el CSV y devuelve cada fila con sus celdas por campo (recortadas; vacías como '').
     * `line` cuenta registros desde el encabezado (fila 1), como en una hoja de cálculo.
     *
     * @param  array<string, string>  $aliases  Encabezado normalizado (sin acentos, minúsculas, espacios simples) => campo
     * @param  list<string>  $required  Campos que deben existir como columnas
     * @return array{error: ?string, rows: list<array{line: int, cells: array<string, string>}>}
     */
    public function read(string $contents, array $aliases, array $required, string $missingMessage): array
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

        $columns = $this->mapHeaders($this->cleanRecord($file->fgetcsv()) ?? [], $aliases);

        foreach ($required as $field) {
            if (! in_array($field, $columns, true)) {
                return ['error' => $missingMessage, 'rows' => []];
            }
        }

        $rows = [];
        $line = 1;

        while (! $file->eof()) {
            $values = $this->cleanRecord($file->fgetcsv());
            $line++;

            if ($values === null) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                return ['error' => 'El archivo tiene más de '.self::MAX_ROWS.' filas. Divídelo en varios archivos.', 'rows' => []];
            }

            $rows[] = ['line' => $line, 'cells' => $this->cells($columns, $values)];
        }

        return ['error' => null, 'rows' => $rows];
    }

    /**
     * Genera un CSV (UTF-8 con BOM, para que Excel abra bien los acentos).
     *
     * @param  list<list<string>>  $rows
     */
    public function write(array $rows): string
    {
        $file = new SplTempFileObject;

        foreach ($rows as $row) {
            $file->fputcsv($row, ',', '"', '');
        }

        $file->rewind();
        $csv = '';

        foreach ($file as $line) {
            $csv .= $line;
        }

        return "\xEF\xBB\xBF".$csv;
    }

    /**
     * Texto en minúsculas, sin acentos y con espacios simples: así "Sitio_Web" y "sitio web" coinciden.
     */
    public function normalize(?string $text): string
    {
        return Str::of((string) $text)->ascii()->lower()->replace('_', ' ')->squish()->toString();
    }

    /**
     * @param  list<?string>  $headers
     * @param  array<string, string>  $aliases
     * @return list<?string>
     */
    private function mapHeaders(array $headers, array $aliases): array
    {
        $seen = [];

        return array_map(function (?string $header) use ($aliases, &$seen): ?string {
            $field = $aliases[$this->normalize($header)] ?? null;

            // Si dos columnas apuntan al mismo campo, gana la primera.
            if ($field === null || isset($seen[$field])) {
                return null;
            }

            return $seen[$field] = $field;
        }, $headers);
    }

    /**
     * @param  list<?string>  $columns
     * @param  list<?string>  $values
     * @return array<string, string>
     */
    private function cells(array $columns, array $values): array
    {
        $cells = [];

        foreach ($columns as $index => $field) {
            if ($field !== null) {
                $cells[$field] = trim((string) ($values[$index] ?? ''));
            }
        }

        return $cells;
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
}
