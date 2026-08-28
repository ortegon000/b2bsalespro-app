<?php

use App\Domain\ObjecionCero\Enums\TipoObjecion;
use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Ficha;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Banco de fichas · Objeción Cero')] class extends Component {
    public string $query = '';

    public ?string $catFilter = null;

    public ?string $tipoFilter = null;

    public ?int $fichaId = null;

    #[Computed]
    public function categorias()
    {
        return Categoria::withCount('fichas')->orderBy('label')->get();
    }

    #[Computed]
    public function tipos(): array
    {
        return TipoObjecion::cases();
    }

    #[Computed]
    public function fichas()
    {
        return Ficha::query()
            ->with('categoria')
            ->when($this->catFilter, fn ($q) => $q->where('categoria_id', $this->catFilter))
            ->when($this->tipoFilter, fn ($q) => $q->where('tipo', $this->tipoFilter))
            ->when($this->query, fn ($q) => $q->where(function ($q) {
                $q->where('objecion', 'like', "%{$this->query}%")
                    ->orWhere('significa', 'like', "%{$this->query}%");
            }))
            ->orderBy('numero')
            ->get();
    }

    #[Computed]
    public function ficha(): ?Ficha
    {
        return $this->fichaId ? Ficha::with('categoria')->find($this->fichaId) : null;
    }

    #[Computed]
    public function fichasTotal(): int
    {
        return Ficha::count();
    }

    public function tipoColor(TipoObjecion $tipo): string
    {
        return match ($tipo) {
            TipoObjecion::Real => 'green',
            TipoObjecion::Duda => 'yellow',
            TipoObjecion::Excusa => 'orange',
        };
    }

    public function toggleCat(string $id): void
    {
        $this->catFilter = $this->catFilter === $id ? null : $id;
    }

    public function toggleTipo(string $id): void
    {
        $this->tipoFilter = $this->tipoFilter === $id ? null : $id;
    }

    public function open(int $numero): void
    {
        $this->fichaId = Ficha::where('numero', $numero)->value('id');
    }

    public function close(): void
    {
        $this->fichaId = null;
    }
}; ?>

<div>
    <div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Banco de fichas</flux:heading>
        <flux:text class="mt-1">{{ $this->fichas->count() }} de {{ $this->fichasTotal }} fichas</flux:text>
    </div>

    <flux:input wire:model.live.debounce.300ms="query" placeholder="Buscar objeción…" icon="magnifying-glass" />

    <div class="flex flex-wrap gap-2">
        @foreach ($this->categorias as $cat)
            <flux:button
                size="sm"
                :variant="$catFilter === (string) $cat->id ? 'primary' : 'outline'"
                wire:click="toggleCat('{{ $cat->id }}')"
            >
                {{ $cat->label }} ({{ $cat->fichas_count ?? '' }})
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($this->tipos as $tipo)
            <flux:button
                size="sm"
                :variant="$tipoFilter === $tipo->value ? 'primary' : 'ghost'"
                wire:click="toggleTipo('{{ $tipo->value }}')"
            >
                {{ $tipo->label() }}
            </flux:button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->fichas as $f)
            <button wire:click="open({{ $f->numero }})" class="text-left" type="button">
                <flux:card class="h-full transition hover:border-zinc-400 dark:hover:border-zinc-500">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:badge size="sm">#{{ str_pad($f->numero, 2, '0', STR_PAD_LEFT) }}</flux:badge>
                        <flux:badge size="sm" :color="$this->tipoColor($f->tipo)">
                            {{ $f->tipo->short() }}
                        </flux:badge>
                    </div>
                    <flux:heading size="sm">{{ $f->objecion }}</flux:heading>
                    <flux:text class="mt-1 line-clamp-2 text-sm">{{ $f->significa }}</flux:text>
                    <flux:text class="mt-2 text-xs uppercase tracking-wide text-zinc-400">{{ $f->categoria->label }}</flux:text>
                </flux:card>
            </button>
        @empty
            <flux:text class="col-span-full text-center">No hay fichas que coincidan con el filtro.</flux:text>
        @endforelse
    </div>
    </div>

    @if ($this->ficha)
    <div x-teleport="body">
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-zinc-900/80 p-4 pt-10" wire:click.self="close">
        <flux:card class="w-full max-w-2xl bg-white! dark:bg-zinc-900!">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <flux:badge size="sm" class="mb-2">#{{ str_pad($this->ficha->numero, 2, '0', STR_PAD_LEFT) }} · {{ $this->ficha->categoria->label }}</flux:badge>
                    <flux:heading size="lg">{{ $this->ficha->objecion }}</flux:heading>
                </div>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="close" />
            </div>

            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="sm" class="mb-1">Cómo confirmarla</flux:heading>
                    <flux:text>{{ $this->ficha->confirmar }}</flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-1">Qué significa</flux:heading>
                    <flux:text>{{ $this->ficha->significa }}</flux:text>
                </div>

                @if (!empty($this->ficha->peor))
                    <div>
                        <flux:heading size="sm" class="mb-1">Lo peor que puedes responder</flux:heading>
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($this->ficha->peor as $p)
                                <li><flux:text>{{ $p }}</flux:text></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($this->ficha->dialogo))
                    <div>
                        <flux:heading size="sm" class="mb-2">Diálogo</flux:heading>
                        <div class="flex flex-col gap-2">
                            @foreach ($this->ficha->dialogo as $m)
                                <div class="flex gap-3">
                                    <span class="w-12 shrink-0 text-xs font-semibold uppercase text-zinc-400">{{ $m['who'] === 't' ? 'Tú' : 'Cliente' }}</span>
                                    <flux:text>{{ $m['t'] }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($this->ficha->consejo)
                    <div>
                        <flux:heading size="sm" class="mb-1">Consejo</flux:heading>
                        <flux:text>{{ $this->ficha->consejo }}</flux:text>
                    </div>
                @endif

                @if ($this->ficha->error)
                    <div>
                        <flux:heading size="sm" class="mb-1">Error común</flux:heading>
                        <flux:text>{{ $this->ficha->error }}</flux:text>
                    </div>
                @endif

                @if (!empty($this->ficha->ramas))
                    <div>
                        <flux:heading size="sm" class="mb-2">Si el cliente responde…</flux:heading>
                        <div class="flex flex-col gap-2">
                            @foreach ($this->ficha->ramas as $r)
                                <div>
                                    <flux:text class="font-medium">{{ $r['r'] }}</flux:text>
                                    <flux:text class="block text-sm text-zinc-400">{{ $r['t'] }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>
    </div>
    @endif
</div>
