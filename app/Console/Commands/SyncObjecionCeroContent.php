<?php

namespace App\Console\Commands;

use App\Domain\ObjecionCero\Services\ContentSyncer;
use Illuminate\Console\Command;

class SyncObjecionCeroContent extends Command
{
    protected $signature = 'objecion-cero:sync-content';

    protected $description = 'Sincroniza el contenido maestro de Objeción Cero desde database/seeders/data/objecion-cero.json (agrega, actualiza y elimina)';

    public function handle(ContentSyncer $syncer): int
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/objecion-cero.json')),
            associative: true,
        );

        $syncer->sync($data);

        $this->info('Contenido de Objeción Cero sincronizado.');

        return self::SUCCESS;
    }
}
