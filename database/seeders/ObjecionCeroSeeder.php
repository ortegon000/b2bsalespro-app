<?php

namespace Database\Seeders;

use App\Domain\ObjecionCero\Services\ContentSyncer;
use Illuminate\Database\Seeder;

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
        $data = json_decode(
            file_get_contents(database_path('seeders/data/objecion-cero.json')),
            associative: true,
        );

        $syncer->sync($data);
    }
}
