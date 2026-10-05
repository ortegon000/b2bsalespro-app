<?php

use App\Domain\Crm\Actions\SendTestEmail;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Services\ReinforcementEmail;
use App\Domain\Crm\Services\SendMetrics;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Secuencia de refuerzo')] class extends Component {
    public Sequence $sequence;

    /** @var array<int, string> Plantilla de Brevo por día (1..30); solo se usa si el día no tiene vista. */
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
     * Asunto de cada día que ya tiene su correo (vista Blade), con datos de ejemplo.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function subjects(): array
    {
        $emails = app(ReinforcementEmail::class);
        $total = $this->sequence->steps->count();

        return collect($emails->availableDays())
            ->mapWithKeys(fn (int $day) => [$day => $emails->render($day, $emails->sampleData($total))['subject']])
            ->all();
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

    /**
     * Manda el correo del día a quien está usando el CRM, para revisarlo en su bandeja.
     */
    public function sendTest(int $day, SendTestEmail $sendTestEmail): void
    {
        abort_unless(array_key_exists($day, $this->subjects), 404);

        try {
            $subject = $sendTestEmail->handle($this->sequence, $day, auth()->user());
        } catch (BrevoException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: "Prueba enviada a ".auth()->user()->email.": {$subject}");
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

<div class="flex max-w-4xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $sequence->name }}</flux:heading>
        <flux:subheading>
            Cada día sale con su correo propio (la vista <code>dia-NN.blade.php</code> en <code>resources/views/emails/crm/reinforcement</code>).
            Los días que todavía no tienen vista salen con la plantilla de Brevo indicada.
        </flux:subheading>
    </div>

    <details class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <summary class="cursor-pointer text-sm font-medium">Pegar lista de IDs de plantillas de Brevo (solo para días sin vista)</summary>
        <form wire:submit="applyPasted" class="mt-3 flex flex-col gap-3">
            <flux:textarea wire:model="pasted" description="Un ID por línea, en orden: la primera línea es el día 1." rows="4" />
            <div><flux:button type="submit" size="sm">Llenar los días</flux:button></div>
        </form>
    </details>

    <form wire:submit="save" class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
            @foreach ($sequence->steps as $step)
                @php($subject = $this->subjects[$step->day] ?? null)
                @php($result = $this->results[$step->day] ?? null)
                <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-3 sm:flex-row sm:items-start sm:justify-between dark:border-zinc-700" wire:key="step-{{ $step->day }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:text variant="strong">Día {{ $step->day }}</flux:text>
                            @if ($subject)
                                <flux:badge size="sm" color="green">Correo propio</flux:badge>
                            @elseif ($step->brevo_template_id || ($templates[$step->day] ?? '') !== '')
                                <flux:badge size="sm" color="blue">Plantilla de Brevo</flux:badge>
                            @else
                                <flux:badge size="sm" color="red">Sin correo</flux:badge>
                            @endif
                        </div>
                        @if ($subject)
                            <flux:text size="sm" class="mt-1 break-words text-zinc-500 dark:text-zinc-400">{{ $subject }}</flux:text>
                        @endif
                        @if (($result['sent'] ?? 0) > 0)
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                {{ $result['sent'] }} env. · {{ $result['opened_rate'] }}% abiertos · {{ $result['clicked_rate'] }}% clics
                            </flux:text>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-end gap-2 sm:shrink-0">
                        @if ($subject)
                            <flux:button size="sm" icon="eye" :href="route('crm.sequences.preview', [$sequence, $step->day])" target="_blank">Vista previa</flux:button>
                            <flux:button size="sm" icon="paper-airplane" type="button" wire:click="sendTest({{ $step->day }})" wire:confirm="Se enviará el correo del día {{ $step->day }} a tu correo ({{ auth()->user()->email }}). ¿Continuar?">Enviarme prueba</flux:button>
                        @endif
                        <flux:field class="w-32">
                            <flux:label class="sr-only">Plantilla de Brevo del día {{ $step->day }}</flux:label>
                            <flux:input wire:model="templates.{{ $step->day }}" type="number" min="1" inputmode="numeric" placeholder="ID Brevo" />
                            <flux:error name="templates.{{ $step->day }}" />
                        </flux:field>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button :href="route('crm.pipeline')" wire:navigate>Volver</flux:button>
        </div>
    </form>
</div>
