<?php

use App\Domain\Crm\Models\Task;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Tareas')] class extends Component {
    #[Url]
    public string $scope = 'mine';

    /**
     * Pendientes agrupadas: vencidas, de hoy, próximas y sin fecha.
     *
     * @return array<string, Collection<int, Task>>
     */
    #[Computed]
    public function groups(): array
    {
        $tasks = Task::query()
            ->pending()
            ->with(['company', 'assignee'])
            ->when($this->scope !== 'all', fn ($query) => $query->where('assigned_to', auth()->id()))
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->orderBy('id')
            ->get();

        $today = Task::today();

        return [
            'Vencidas' => $tasks->filter(fn (Task $task) => $task->due_on && $task->due_on->toDateString() < $today),
            'Hoy' => $tasks->filter(fn (Task $task) => $task->due_on?->toDateString() === $today),
            'Próximas' => $tasks->filter(fn (Task $task) => $task->due_on && $task->due_on->toDateString() > $today),
            'Sin fecha' => $tasks->filter(fn (Task $task) => $task->due_on === null),
        ];
    }

    public function complete(int $id): void
    {
        Task::findOrFail($id)->update(['completed_at' => now()]);

        unset($this->groups);
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">Tareas</flux:heading>
            <flux:subheading>Lo pendiente en las empresas del pipeline.</flux:subheading>
        </div>

        <flux:button.group>
            <flux:button size="sm" :variant="$scope === 'mine' ? 'primary' : 'outline'" wire:click="$set('scope', 'mine')">Mías</flux:button>
            <flux:button size="sm" :variant="$scope === 'all' ? 'primary' : 'outline'" wire:click="$set('scope', 'all')">Todas</flux:button>
        </flux:button.group>
    </div>

    @php($total = collect($this->groups)->sum(fn ($group) => $group->count()))

    @if ($total === 0)
        <flux:text class="text-zinc-500">No hay tareas pendientes. Agrégalas desde la ficha de cada empresa.</flux:text>
    @endif

    @foreach ($this->groups as $label => $tasks)
        @if ($tasks->isNotEmpty())
            <section class="flex flex-col gap-2" wire:key="group-{{ $label }}">
                <flux:heading size="lg" class="{{ $label === 'Vencidas' ? 'text-red-600 dark:text-red-400' : '' }}">{{ $label }} ({{ $tasks->count() }})</flux:heading>

                @foreach ($tasks as $task)
                    <div class="flex items-start gap-3 rounded-lg border p-3 {{ $label === 'Vencidas' ? 'border-red-300 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }}" wire:key="task-{{ $task->id }}">
                        <flux:checkbox wire:click="complete({{ $task->id }})" aria-label="Completar tarea" />
                        <div class="min-w-0">
                            <flux:text variant="strong">{{ $task->title }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                <flux:link :href="route('crm.companies.show', $task->company)" wire:navigate>{{ $task->company->name }}</flux:link>
                                @if ($task->due_on) · {{ $task->due_on->format('d/m/Y') }}@endif
                                @if ($scope === 'all' && $task->assignee) · {{ $task->assignee->name }}@endif
                            </flux:text>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endforeach
</div>
