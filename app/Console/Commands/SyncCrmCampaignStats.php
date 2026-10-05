<?php

namespace App\Console\Commands;

use App\Domain\Crm\Actions\SyncCampaignStats;
use Illuminate\Console\Command;

class SyncCrmCampaignStats extends Command
{
    protected $signature = 'crm:sync-campaign-stats';

    protected $description = 'Trae de Brevo las métricas de las campañas del newsletter enviadas en los últimos 30 días';

    public function handle(SyncCampaignStats $syncCampaignStats): int
    {
        $this->info("Campañas actualizadas: {$syncCampaignStats->handle()}");

        return self::SUCCESS;
    }
}
