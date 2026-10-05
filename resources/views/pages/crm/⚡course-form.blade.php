<?php

use App\Domain\Crm\Enums\CourseModality;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Sequence;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Curso')] class extends Component {
    public ?Course $course = null;

    public Company $company;

    public string $title = '';
    public string $modality = 'in_person';
    public ?int $hours = null;
    public string $startsOn = '';
    public string $endsOn = '';
    public ?int $sequenceId = null;

    public function mount(?Company $company = null, ?Course $course = null): void
    {
        if ($course?->exists) {
            $this->course = $course;
            $this->company = $course->company;
            $this->title = $course->title;
            $this->modality = $course->modality->value;
            $this->hours = $course->hours;
            $this->startsOn = $course->starts_on?->toDateString() ?? '';
            $this->endsOn = $course->ends_on?->toDateString() ?? '';
            $this->sequenceId = $course->sequence_id;

            return;
        }

        $this->course = null;
        $this->company = $company;
        $this->sequenceId = Sequence::orderBy('id')->value('id');
    }

    /**
     * @return Collection<int, Sequence>
     */
    #[Computed]
    public function sequences(): Collection
    {
        return Sequence::orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'modality' => ['required', Rule::enum(CourseModality::class)],
            'hours' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'startsOn' => ['nullable', 'date'],
            'endsOn' => ['nullable', 'date', 'after_or_equal:startsOn'],
            'sequenceId' => ['nullable', Rule::exists('crm_sequences', 'id')],
        ]);

        $attributes = [
            'title' => $validated['title'],
            'modality' => $validated['modality'],
            'hours' => $validated['hours'],
            'starts_on' => $validated['startsOn'] ?: null,
            'ends_on' => $validated['endsOn'] ?: null,
        ];

        if ($this->course) {
            // Cambiar de secuencia con el refuerzo activo dejaría envíos huérfanos.
            if (! $this->course->isReinforcementActive()) {
                $attributes['sequence_id'] = $validated['sequenceId'];
            }

            $this->course->update($attributes);
            $course = $this->course;
        } else {
            $course = $this->company->courses()->create($attributes + ['sequence_id' => $validated['sequenceId']]);
        }

        Flux::toast(variant: 'success', text: 'Curso guardado.');

        $this->redirectRoute('crm.courses.show', $course, navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $course ? 'Editar curso' : 'Nuevo curso' }}</flux:heading>
        <flux:subheading>{{ $company->name }}</flux:subheading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        <flux:input wire:model="title" label="Nombre del curso" required autofocus />

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:select wire:model="modality" label="Modalidad">
                @foreach (CourseModality::cases() as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="hours" type="number" min="1" label="Horas" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="startsOn" type="date" label="Inicio" />
            <flux:input wire:model="endsOn" type="date" label="Fin" />
        </div>

        <flux:select wire:model="sequenceId" label="Secuencia de refuerzo" placeholder="Sin secuencia" :disabled="$course?->isReinforcementActive()">
            @foreach ($this->sequences as $sequence)
                <flux:select.option :value="$sequence->id">{{ $sequence->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button :href="$course ? route('crm.courses.show', $course) : route('crm.companies.show', $company)" wire:navigate>Cancelar</flux:button>
        </div>
    </form>
</div>
