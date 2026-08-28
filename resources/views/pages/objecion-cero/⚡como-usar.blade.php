<?php

use App\Domain\ObjecionCero\Models\UsoItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cómo usar · Objeción Cero')] class extends Component {
    #[Computed]
    public function items()
    {
        return UsoItem::orderBy('orden')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Cómo usar el manual</flux:heading>
    </div>

    <div class="flex flex-col gap-4">
        @foreach ($this->items as $u)
            <flux:card>
                <flux:heading size="sm" class="mb-1">{{ $u->titulo }}</flux:heading>
                <flux:text>{{ $u->descripcion }}</flux:text>
            </flux:card>
        @endforeach
    </div>
</div>
