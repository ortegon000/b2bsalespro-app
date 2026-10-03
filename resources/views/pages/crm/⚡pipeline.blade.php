<?php

use App\Domain\Crm\Actions\MoveCompanyToStage;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pipeline')] class extends Component {
    /**
     * @return Collection<int, Stage>
     */
    #[Computed]
    public function stages(): Collection
    {
        return Stage::query()
            ->orderBy('position')
            ->with(['companies' => fn ($query) => $query
                ->with('owner')
                ->withCount('contacts')
                ->orderByDesc('stage_changed_at')
                ->orderByDesc('id')])
            ->get();
    }

    /**
     * Se llama al soltar una tarjeta en una columna. El orden dentro de la
     * columna lo define la antigüedad en la etapa, así que solo importa la columna destino.
     */
    public function moveCompany(int $id, int $position, int $stageId, MoveCompanyToStage $moveCompanyToStage): void
    {
        $moveCompanyToStage->handle(Company::findOrFail($id), Stage::findOrFail($stageId));

        unset($this->stages);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Pipeline</flux:heading>
            <flux:subheading>Arrastra las empresas entre etapas del flujo comercial.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('crm.companies.create')" wire:navigate>Nueva empresa</flux:button>
    </div>

    {{-- Las columnas hacen scroll horizontal dentro de su contenedor, no la página --}}
    <div class="-mx-4 flex gap-4 overflow-x-auto px-4 pb-4 md:mx-0 md:px-0">
        @foreach ($this->stages as $stage)
            <section class="flex w-72 shrink-0 flex-col rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900" wire:key="stage-{{ $stage->id }}">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <flux:heading>{{ $stage->name }}</flux:heading>
                    <flux:badge size="sm">{{ $stage->companies->count() }}</flux:badge>
                </div>

                {{-- En táctil el arrastre requiere mantener presionado, para no bloquear el scroll del tablero --}}
                <div class="flex min-h-24 flex-1 flex-col gap-2" wire:sort="moveCompany" wire:sort:config="{ delay: 200, delayOnTouchOnly: true }" wire:sort:group="companies" wire:sort:group-id="{{ $stage->id }}">
                    @foreach ($stage->companies as $company)
                        <div wire:key="company-{{ $company->id }}" wire:sort:item="{{ $company->id }}"
                            class="cursor-grab rounded-md border border-zinc-200 bg-white p-3 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-500">
                            <a href="{{ route('crm.companies.show', $company) }}" wire:navigate class="font-medium text-zinc-800 hover:underline dark:text-white">{{ $company->name }}</a>
                            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                                {{ $company->source->label() }} · {{ trans_choice('{0} Sin contactos|{1} 1 contacto|[2,*] :count contactos', $company->contacts_count) }}
                            </flux:text>
                            @if ($company->owner)
                                <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $company->owner->name }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
