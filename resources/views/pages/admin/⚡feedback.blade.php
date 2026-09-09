<?php

use App\Domain\ObjecionCero\Models\Feedback;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Feedback de la beta')] class extends Component {
    use WithPagination;

    #[Computed]
    public function feedback()
    {
        return Feedback::with('user')->latest()->paginate(20);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Feedback de la beta</flux:heading>
        <flux:subheading>Lo que los invitados de Objeción Cero han enviado desde el botón "💬 Feedback".</flux:subheading>
    </div>

    @if ($this->feedback->isEmpty())
        <flux:text class="text-zinc-500">Todavía no hay feedback registrado.</flux:text>
    @else
        <flux:table :paginate="$this->feedback">
            <flux:table.columns>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Usuario</flux:table.column>
                <flux:table.column>Página</flux:table.column>
                <flux:table.column>Mensaje</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->feedback as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell class="whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $item->user?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->page ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="max-w-md whitespace-normal">{{ $item->message }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
