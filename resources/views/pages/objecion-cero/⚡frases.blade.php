<?php

use App\Domain\ObjecionCero\Models\Frase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('100 frases · Objeción Cero')] class extends Component {
    #[Computed]
    public function grupos()
    {
        return Frase::orderBy('orden')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Frases de transición</flux:heading>
        <flux:text class="mt-1">Frases listas para cada momento de la conversación.</flux:text>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($this->grupos as $g)
            <details class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/10">
                <summary class="flex cursor-pointer list-none items-center justify-between">
                    <flux:heading size="sm">{{ $g->titulo }}</flux:heading>
                    <flux:badge size="sm">{{ count($g->items) }}</flux:badge>
                </summary>
                <ul class="mt-4 list-disc space-y-2 pl-5">
                    @foreach ($g->items as $frase)
                        <li><flux:text>{{ $frase }}</flux:text></li>
                    @endforeach
                </ul>
            </details>
        @endforeach
    </div>
</div>
