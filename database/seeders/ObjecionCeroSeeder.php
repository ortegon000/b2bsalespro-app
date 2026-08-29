<?php

namespace Database\Seeders;

use App\Domain\ObjecionCero\Services\ContentSyncer;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Carga el contenido maestro de Objeción Cero desde el JSON extraído
 * del bundle original (resources/legacy/objecion-cero-origen.html).
 *
 * Fuente: database/seeders/data/objecion-cero.json
 */
class ObjecionCeroSeeder extends Seeder
{
    public function run(ContentSyncer $syncer): void
    {
        $path = database_path('seeders/data/objecion-cero.json');
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("No se pudo leer el archivo de contenido: {$path}");
        }

        $data = json_decode($json, associative: true);

        $syncer->sync($data);
    }
}
