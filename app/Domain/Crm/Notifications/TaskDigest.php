<?php

namespace App\Domain\Crm\Notifications;

use App\Domain\Crm\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Resumen diario de las tareas del CRM que vencen hoy o ya vencieron.
 */
class TaskDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Task>  $tasks
     */
    public function __construct(public Collection $tasks) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $overdue = $this->tasks->filter->isOverdue()->count();

        $message = (new MailMessage)
            ->subject(trans_choice('{1} Tienes 1 tarea del CRM para hoy|[2,*] Tienes :count tareas del CRM para hoy', $this->tasks->count()).($overdue > 0 ? ' ('.trans_choice('{1} :count vencida|[2,*] :count vencidas', $overdue).')' : ''))
            ->greeting("Hola, {$notifiable->name}");

        foreach ($this->tasks as $task) {
            $when = $task->isOverdue() ? 'vencida el '.$task->due_on?->format('d/m/Y') : 'para hoy';
            $message->line("• {$task->company->name}: {$task->title} ({$when})");
        }

        return $message->action('Ver mis tareas', route('crm.tasks'));
    }
}
