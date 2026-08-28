<?php

use App\Domain\ObjecionCero\Models\PlantillaPaso;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Plantilla personal · Objeción Cero')] class extends Component {
    #[Computed]
    public function pasos()
    {
        return PlantillaPaso::orderBy('orden')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Plantilla de personalización</flux:heading>
        <flux:text class="mt-1">Los campos que debes completar para adaptar los scripts a tu negocio.</flux:text>
    </div>

    <div class="flex flex-col gap-4">
        @foreach ($this->pasos as $p)
            <flux:card>
                <flux:heading size="sm" class="mb-3">{{ $p->paso }}</flux:heading>
                <div class="flex flex-col gap-3">
                    @foreach ($p->campos as $campo)
                        <div>
                            <flux:text class="font-medium">{{ $campo['label'] }}</flux:text>
                            @if (!empty($campo['ej']))
                                <flux:text class="mt-0.5 text-sm text-zinc-400">Ej: {{ $campo['ej'] }}</flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
