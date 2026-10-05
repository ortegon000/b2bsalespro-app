<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Task;
use App\Domain\Crm\Models\TeamMember;
use App\Domain\Crm\Notifications\TaskDigest;

class SendTaskDigest
{
    /**
     * Avisa por correo a cada persona del equipo con tareas pendientes que vencen hoy o ya vencieron.
     * No envía nada a quien no tiene tareas ni a las tareas sin responsable.
     *
     * @return int Cantidad de personas avisadas
     */
    public function handle(): int
    {
        $tasks = Task::query()
            ->dueByToday()
            ->whereNotNull('assigned_to')
            ->whereIn('assigned_to', TeamMember::pluck('user_id'))
            ->with(['company', 'assignee'])
            ->orderBy('due_on')
            ->orderBy('id')
            ->get()
            ->groupBy('assigned_to');

        foreach ($tasks as $userTasks) {
            $userTasks->first()?->assignee?->notify(new TaskDigest($userTasks->values()));
        }

        return $tasks->count();
    }
}
