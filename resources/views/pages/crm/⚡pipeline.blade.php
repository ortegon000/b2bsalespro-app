<?php

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
                ->withCount('contacts')
                ->orderByDesc('stage_changed_at')
                ->orderByDesc('id')])
            ->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Pipeline</flux:heading>
        <flux:subheading>Empresas por etapa del flujo comercial.</flux:subheading>
    </div>

    {{-- Las columnas hacen scroll horizontal dentro de su contenedor, no la página --}}
    <div class="-mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-4 md:mx-0 md:px-0">
        @foreach ($this->stages as $stage)
            <section class="w-72 shrink-0 snap-start rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900" wire:key="stage-{{ $stage->id }}">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <flux:heading>{{ $stage->name }}</flux:heading>
                    <flux:badge size="sm">{{ $stage->companies->count() }}</flux:badge>
                </div>

                <div class="flex flex-col gap-2">
                    @forelse ($stage->companies as $company)
                        <div class="rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800" wire:key="company-{{ $company->id }}">
                            <flux:text variant="strong">{{ $company->name }}</flux:text>
                            <flux:text class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                                {{ $company->source->label() }} · {{ trans_choice('{0} Sin contactos|{1} 1 contacto|[2,*] :count contactos', $company->contacts_count) }}
                            </flux:text>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">Sin empresas</flux:text>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
