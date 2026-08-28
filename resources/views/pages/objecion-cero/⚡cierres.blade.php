<?php

use App\Domain\ObjecionCero\Models\Cierre;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Selector de cierres · Objeción Cero')] class extends Component {
    #[Computed]
    public function cierres()
    {
        return Cierre::orderBy('orden')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Selector de cierres</flux:heading>
        <flux:text class="mt-1">{{ $this->cierres->count() }} cierres, cada uno con cuándo usarlo y cuándo no.</flux:text>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($this->cierres as $c)
            <flux:card>
                <flux:badge size="sm" class="mb-2">{{ $c->objecion }}</flux:badge>
                <flux:heading size="sm">{{ $c->nombre }}</flux:heading>
                <flux:text class="mt-2 italic">"{{ $c->script }}"</flux:text>

                <div class="mt-3 flex flex-col gap-1 text-sm">
                    <flux:text class="text-emerald-600 dark:text-emerald-400"><strong>Úsalo:</strong> {{ $c->usar }}</flux:text>
                    <flux:text class="text-red-600 dark:text-red-400"><strong>No lo uses:</strong> {{ $c->no_usar }}</flux:text>
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
