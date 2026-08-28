<?php

use App\Domain\ObjecionCero\Models\WhatsappScript;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scripts de WhatsApp · Objeción Cero')] class extends Component {
    public ?int $scriptId = null;

    #[Computed]
    public function scripts()
    {
        return WhatsappScript::orderBy('orden')->get();
    }

    #[Computed]
    public function script(): ?WhatsappScript
    {
        return $this->scriptId ? WhatsappScript::find($this->scriptId) : $this->scripts->first();
    }

    public function select(int $id): void
    {
        $this->scriptId = $id;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Scripts de WhatsApp</flux:heading>
        <flux:text class="mt-1">{{ $this->scripts->count() }} conversaciones completas, listas para adaptar.</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-2 lg:col-span-1">
            @foreach ($this->scripts as $s)
                <button
                    type="button"
                    wire:click="select({{ $s->id }})"
                    class="rounded-lg border px-3 py-2 text-left text-sm transition
                        {{ $this->script?->id === $s->id
                            ? 'border-zinc-400 bg-zinc-100 dark:border-zinc-500 dark:bg-white/10'
                            : 'border-transparent hover:bg-zinc-50 dark:hover:bg-white/5' }}"
                >
                    {{ $s->titulo }}
                </button>
            @endforeach
        </div>

        <flux:card class="lg:col-span-2">
            @if ($this->script)
                <flux:heading size="sm" class="mb-4">{{ $this->script->titulo }}</flux:heading>
                <div class="flex flex-col gap-2">
                    @foreach ($this->script->mensajes as $m)
                        <div class="flex {{ $m['who'] === 't' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[82%] rounded-2xl px-3 py-2 text-sm
                                {{ $m['who'] === 't'
                                    ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100'
                                    : 'bg-zinc-100 text-zinc-800 dark:bg-white/10 dark:text-zinc-100' }}">
                                {{ $m['t'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </div>
</div>
