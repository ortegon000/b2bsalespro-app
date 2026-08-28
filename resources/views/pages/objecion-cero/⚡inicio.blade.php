<?php

use App\Domain\ObjecionCero\Models\Categoria;
use App\Domain\ObjecionCero\Models\Cierre;
use App\Domain\ObjecionCero\Models\Ficha;
use App\Domain\ObjecionCero\Models\Frase;
use App\Domain\ObjecionCero\Models\PreguntaGrupo;
use App\Domain\ObjecionCero\Models\WhatsappScript;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Objeción Cero')] class extends Component {
    public array $stats = [];

    public $categorias;

    public function mount(): void
    {
        $this->stats = [
            ['k' => Ficha::count(), 'v' => 'fichas de objeción'],
            ['k' => Categoria::count(), 'v' => 'categorías'],
            ['k' => PreguntaGrupo::all()->sum(fn ($g) => count($g->items)), 'v' => 'preguntas listas'],
            ['k' => WhatsappScript::count(), 'v' => 'scripts de WhatsApp'],
        ];

        $this->categorias = Categoria::withCount('fichas')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Objeción Cero</flux:heading>
        <flux:text class="mt-1">Manual de manejo de objeciones para ventas B2B.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($stats as $s)
            <flux:card class="text-center">
                <flux:heading size="xl">{{ $s['k'] }}</flux:heading>
                <flux:text class="mt-1">{{ $s['v'] }}</flux:text>
            </flux:card>
        @endforeach
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">Categorías</flux:heading>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
            @foreach ($categorias as $cat)
                <flux:card>
                    <div class="flex items-center justify-between">
                        <flux:text class="font-medium">{{ $cat->label }}</flux:text>
                        <flux:badge size="sm">{{ $cat->fichas_count }}</flux:badge>
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</div>
