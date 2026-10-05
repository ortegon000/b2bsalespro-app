<?php

namespace App\Console\Commands;

use App\Domain\Crm\Actions\SendTaskDigest;
use Illuminate\Console\Command;

class SendCrmTaskDigest extends Command
{
    protected $signature = 'crm:send-task-digest';

    protected $description = 'Envía a cada persona del equipo el resumen de sus tareas del CRM que vencen hoy o ya vencieron';

    public function handle(SendTaskDigest $sendTaskDigest): int
    {
        $this->info("Personas avisadas: {$sendTaskDigest->handle()}");

        return self::SUCCESS;
    }
}
