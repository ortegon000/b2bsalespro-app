<?php

namespace App\Console\Commands;

use App\Domain\Crm\Actions\DispatchDueSends;
use Illuminate\Console\Command;

class DispatchReinforcementSends extends Command
{
    protected $signature = 'crm:dispatch-sends';

    protected $description = 'Encola los correos de refuerzo del CRM cuya hora de envío ya llegó';

    public function handle(DispatchDueSends $dispatchDueSends): int
    {
        $this->info("Envíos encolados: {$dispatchDueSends->handle()}");

        return self::SUCCESS;
    }
}
