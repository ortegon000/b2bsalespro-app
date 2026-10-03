<?php

use App\Domain\Crm\Actions\MoveCompanyToStage;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;
use App\Domain\Crm\Models\TeamMember;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Empresa')] class extends Component {
    public ?Company $company = null;

    public string $name = '';
    public string $industry = '';
    public string $website = '';
    public string $notes = '';
    public string $source = 'manual';
    public ?int $stageId = null;
    public ?int $ownerId = null;
    public string $lostReason = '';

    public function mount(?Company $company = null): void
    {
        if ($company?->exists) {
            $this->company = $company;
            $this->name = $company->name;
            $this->industry = $company->industry ?? '';
            $this->website = $company->website ?? '';
            $this->notes = $company->notes ?? '';
            $this->source = $company->source->value;
            $this->stageId = $company->stage_id;
            $this->ownerId = $company->owner_id;
            $this->lostReason = $company->lost_reason ?? '';

            return;
        }

        $this->company = null;
        $this->stageId = Stage::where('type', StageType::Open)->orderBy('position')->value('id');
        $this->ownerId = auth()->id();
    }

    /**
     * @return Collection<int, Stage>
     */
    #[Computed]
    public function stages(): Collection
    {
        return Stage::orderBy('position')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    #[Computed]
    public function owners(): \Illuminate\Support\Collection
    {
        return TeamMember::with('user')->get()->map->user->sortBy('name')->values();
    }

    #[Computed]
    public function isLostStage(): bool
    {
        return $this->stages->firstWhere('id', $this->stageId)?->type === StageType::Lost;
    }

    public function save(MoveCompanyToStage $moveCompanyToStage): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'stageId' => ['required', Rule::exists('crm_stages', 'id')],
            'ownerId' => ['nullable', Rule::exists('crm_team_members', 'user_id')],
            'lostReason' => ['nullable', 'string', 'max:255'],
        ]);

        $stage = Stage::findOrFail($validated['stageId']);

        $attributes = [
            'name' => $validated['name'],
            'industry' => $validated['industry'] ?: null,
            'website' => $validated['website'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'source' => $validated['source'],
            'owner_id' => $validated['ownerId'],
            'lost_reason' => $stage->type === StageType::Lost ? ($validated['lostReason'] ?: null) : null,
        ];

        if ($this->company) {
            $this->company->update($attributes);
            $moveCompanyToStage->handle($this->company, $stage);
            $company = $this->company;
        } else {
            $company = Company::create($attributes + [
                'stage_id' => $stage->id,
                'stage_changed_at' => now(),
            ]);
        }

        Flux::toast(variant: 'success', text: 'Empresa guardada.');

        $this->redirectRoute('crm.companies.show', $company, navigate: true);
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $company ? 'Editar empresa' : 'Nueva empresa' }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        <flux:input wire:model="name" label="Nombre de la empresa" required autofocus />

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="industry" label="Giro" />
            <flux:input wire:model="website" label="Sitio web" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:select wire:model.live="stageId" label="Etapa">
                @foreach ($this->stages as $stage)
                    <flux:select.option :value="$stage->id">{{ $stage->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="ownerId" label="Responsable" placeholder="Sin asignar">
                @foreach ($this->owners as $owner)
                    <flux:select.option :value="$owner->id">{{ $owner->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->isLostStage)
            <flux:input wire:model="lostReason" label="Motivo de pérdida" />
        @endif

        <flux:select wire:model="source" label="Origen">
            @foreach (LeadSource::cases() as $source)
                <flux:select.option :value="$source->value">{{ $source->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea wire:model="notes" label="Notas" rows="4" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Guardar</flux:button>
            <flux:button :href="$company ? route('crm.companies.show', $company) : route('crm.pipeline')" wire:navigate>Cancelar</flux:button>
        </div>
    </form>
</div>
