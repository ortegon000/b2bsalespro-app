<?php

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Services\SendMetrics;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Secuencia de refuerzo')] class extends Component {
    public Sequence $sequence;

    /** @var array<int, string> Plantilla de Brevo por día (1..30). */
    public array $templates = [];

    public string $pasted = '';

    public function mount(Sequence $sequence): void
    {
        $this->sequence = $sequence;

        foreach ($sequence->steps as $step) {
            $this->templates[$step->day] = (string) ($step->brevo_template_id ?? '');
        }
    }

    /**
     * Llena los días en orden con una lista de IDs, uno por línea (o separados por comas).
     */
    public function applyPasted(): void
    {
        $ids = preg_split('/[\s,;]+/', trim($this->pasted), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($ids as $index => $id) {
            if (isset($this->templates[$index + 1])) {
                $this->templates[$index + 1] = $id;
            }
        }

        $this->reset('pasted');
    }

    /**
     * Resultados de cada día de la secuencia, sumando todos los cursos.
     *
     * @return array<int, array{sent: int, opened_rate: ?int, clicked_rate: ?int}>
     */
    #[Computed]
    public function results(): array
    {
        $metrics = app(SendMetrics::class);

        return $this->sequence->steps()
            ->withCount([
                'sends as sent_count' => fn ($query) => $query->where('status', SendStatus::Sent),
                'sends as opened_count' => fn ($query) => $query->whereNotNull('opened_at'),
                'sends as clicked_count' => fn ($query) => $query->whereNotNull('clicked_at'),
            ])
            ->get()
            ->mapWithKeys(fn ($step) => [$step->day => [
                'sent' => $step->sent_count,
                'opened_rate' => $metrics->rate($step->opened_count, $step->sent_count),
                'clicked_rate' => $metrics->rate($step->clicked_count, $step->sent_count),
            ]])
            ->all();
    }

    public function save(): void
    {
        $this->validate([
            'templates.*' => ['nullable', 'integer', 'min:1'],
        ], [
            'templates.*.integer' => 'El ID de plantilla debe ser un número.',
            'templates.*.min' => 'El ID de plantilla debe ser un número positivo.',
        ]);

        foreach ($this->sequence->steps as $step) {
            $id = $this->templates[$step->day] ?? '';
            $step->update(['brevo_template_id' => $id === '' ? null : (int) $id]);
        }

        Flux::toast(variant: 'success', text: 'Secuencia guardada.');
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $sequence->name }}</flux:heading>
        <flux:subheading>ID de la plantilla de Brevo que se envía cada día. Los cambios también aplican a los envíos ya programados que aún no salen.</flux:subheading>
    </div>

    <form wire:submit="applyPasted" class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:textarea wire:model="pasted" label="Pegar lista de IDs" description="Un ID por línea, en orden: la primera línea es el día 1." rows="4" />
        <div><flux:button type="submit" size="sm">Llenar los días</flux:button></div>
    </form>

    <form wire:submit="save" class="flex flex-col gap-4">
        <div class="grid grid-cols-2 items-start gap-3 sm:grid-cols-3 md:grid-cols-5">
            @foreach ($sequence->steps as $step)
                <flux:field wire:key="step-{{ $step->day }}">
                    <flux:label>Día {{ $step->day }}</flux:label>
                    <flux:input wire:model="templates.{{ $step->day }}" type="number" min="1" inputmode="numeric" />
                    <flux:error name="templates.{{ $step->day }}" />
                    @if (($this->results[$step->day]['sent'] ?? 0) > 0)
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            {{ $this->results[$step->day]['sent'] }} env. · {{ $this->results[$step->day]['opened_rate'] }}% abiertos · {{ $this->results[$step->day]['clicked_rate'] }}% clics
                        </flux:text>
                    @endif
                </flux:field>
            @endforeach
        </div>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button :href="route('crm.pipeline')" wire:navigate>Volver</flux:button>
        </div>
    </form>
</div>
