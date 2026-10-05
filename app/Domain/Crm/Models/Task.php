<?php

namespace App\Domain\Crm\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Crm\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $assigned_to
 * @property string $title
 * @property Carbon|null $due_on
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(TaskFactory::class)]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $table = 'crm_tasks';

    protected $fillable = ['company_id', 'assigned_to', 'title', 'due_on', 'completed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Fecha de hoy en la zona del negocio (las tareas vencen por día, no por hora).
     */
    public static function today(): string
    {
        return CarbonImmutable::now(config('crm.timezone'))->toDateString();
    }

    /**
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    /**
     * Pendientes con fecha anterior a hoy.
     *
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNull('completed_at')->whereDate('due_on', '<', self::today());
    }

    /**
     * Pendientes que vencen hoy o antes: lo que hay que atender ya.
     *
     * @param  Builder<Task>  $query
     */
    #[Scope]
    protected function dueByToday(Builder $query): void
    {
        $query->whereNull('completed_at')->whereDate('due_on', '<=', self::today());
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_on !== null && $this->due_on->toDateString() < self::today();
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
