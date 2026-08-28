<?php

use App\Domain\ObjecionCero\Models\Checklist;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checklists · Objeción Cero')] class extends Component {
    #[Computed]
    public function checklists()
    {
        return Checklist::orderBy('orden')->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Checklists</flux:heading>
        <flux:text class="mt-1">Antes, durante y después de la reunión.</flux:text>
    </div>

    <div class="flex flex-col gap-6">
        @foreach ($this->checklists as $cl)
            <flux:card>
                <flux:heading size="lg">{{ $cl->titulo }}</flux:heading>
                @if ($cl->sub)
                    <flux:text class="mt-1">{{ $cl->sub }}</flux:text>
                @endif

                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($cl->bloques as $b)
                        <div>
                            <flux:heading size="sm" class="mb-2">{{ $b['h'] }}</flux:heading>
                            <ul class="space-y-2">
                                @foreach ($b['items'] as $item)
                                    <li class="flex gap-2 text-sm">
                                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                @if ($cl->nota)
                    <flux:callout class="mt-4" icon="light-bulb" heading="Nota">
                        {{ $cl->nota }}
                    </flux:callout>
                @endif
            </flux:card>
        @endforeach
    </div>
</div>
