<?php

namespace App\Console\Commands;

use App\Domain\ObjecionCero\Services\ContentSyncer;
use Illuminate\Console\Command;
use RuntimeException;

class SyncObjecionCeroContent extends Command
{
    protected $signature = 'objecion-cero:sync-content';

    protected $description = 'Sincroniza el contenido maestro de Objeción Cero desde database/seeders/data/objecion-cero.json (agrega, actualiza y elimina)';

    public function handle(ContentSyncer $syncer): int
    {
        $path = database_path('seeders/data/objecion-cero.json');
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("No se pudo leer el archivo de contenido: {$path}");
        }

        $data = json_decode($json, associative: true);

        $syncer->sync($data);

        $this->info('Contenido de Objeción Cero sincronizado.');

        return self::SUCCESS;
    }
}
