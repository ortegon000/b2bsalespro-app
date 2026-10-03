<?php

use App\Domain\Crm\Actions\ImportContacts;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Services\ContactCsv;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Importar contactos')] class extends Component {
    use WithFileUploads;

    public Company $company;

    public $file = null;

    public function updatedFile(): void
    {
        try {
            $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']], [
                'file.mimes' => 'El archivo debe ser un CSV.',
                'file.max' => 'El archivo no puede pesar más de 1 MB.',
            ]);
        } catch (ValidationException $exception) {
            $this->reset('file');

            throw $exception;
        }
    }

    /**
     * @return ?array{error: ?string, rows: list<array<string, mixed>>}
     */
    #[Computed]
    public function preview(): ?array
    {
        return $this->file ? app(ContactCsv::class)->parse($this->file->get(), $this->company) : null;
    }

    #[Computed]
    public function counts(): array
    {
        $rows = collect($this->preview['rows'] ?? []);

        return [
            'create' => $rows->where('status', 'create')->count(),
            'update' => $rows->where('status', 'update')->count(),
            'error' => $rows->where('status', 'error')->count(),
        ];
    }

    public function import(ImportContacts $importContacts, ContactCsv $contactCsv): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        // Se vuelve a leer el archivo subido: no se confía en lo que muestra la vista previa.
        $parsed = $contactCsv->parse($this->file->get(), $this->company);

        if ($parsed['error'] !== null) {
            $this->addError('file', $parsed['error']);

            return;
        }

        $result = $importContacts->handle($this->company, $parsed['rows']);

        Flux::toast(variant: 'success', text: "{$result['created']} contactos nuevos y {$result['updated']} actualizados.");

        $this->redirectRoute('crm.companies.show', $this->company, navigate: true);
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Importar contactos</flux:heading>
        <flux:subheading>{{ $company->name }}</flux:subheading>
    </div>

    <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>
            1. Descarga la plantilla y llénala con un contacto por fila. Solo <strong>nombre</strong> y <strong>email</strong> son obligatorios.
        </flux:text>
        <div>
            <flux:button size="sm" icon="arrow-down-tray" :href="route('crm.contacts.template')">Descargar plantilla</flux:button>
        </div>
        <flux:text>
            2. Sube el archivo. Verás una vista previa antes de importar. Si un email ya existe en esta empresa (o sin empresa) se actualiza; si pertenece a otra empresa se omite.
        </flux:text>
        <div>
            <input type="file" wire:model="file" accept=".csv,text/csv" class="block w-full text-sm text-zinc-600 file:me-3 file:rounded-md file:border-0 file:bg-zinc-200 file:px-3 file:py-2 file:text-sm file:font-medium dark:text-zinc-300 dark:file:bg-zinc-700" />
            <flux:error name="file" />
            <div wire:loading wire:target="file"><flux:text class="mt-2">Leyendo archivo…</flux:text></div>
        </div>
    </div>

    @if ($this->preview)
        @if ($this->preview['error'])
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="$this->preview['error']" />
        @else
            <div class="flex flex-wrap gap-2">
                <flux:badge color="green">{{ $this->counts['create'] }} nuevos</flux:badge>
                <flux:badge color="blue">{{ $this->counts['update'] }} a actualizar</flux:badge>
                <flux:badge color="red">{{ $this->counts['error'] }} con error (se omiten)</flux:badge>
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($this->preview['rows'] as $row)
                    <div class="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-3 {{ $row['status'] === 'error' ? 'border-red-300 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }}" wire:key="row-{{ $row['line'] }}">
                        <div class="min-w-0">
                            <flux:text variant="strong">{{ $row['data']['name'] ?: '—' }}</flux:text>
                            <flux:text class="break-all text-zinc-500 dark:text-zinc-400">
                                {{ $row['data']['email'] ?: '—' }}@if ($row['data']['phone']) · {{ $row['data']['phone'] }}@endif @if ($row['data']['job_title']) · {{ $row['data']['job_title'] }}@endif
                            </flux:text>
                            @if ($row['message'])
                                <flux:text class="mt-1 text-red-600 dark:text-red-400">{{ $row['message'] }}</flux:text>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <flux:text size="sm" class="text-zinc-500">Fila {{ $row['line'] }}</flux:text>
                            <flux:badge size="sm" :color="match ($row['status']) { 'create' => 'green', 'update' => 'blue', default => 'red' }">
                                {{ match ($row['status']) { 'create' => 'Nuevo', 'update' => 'Actualizar', default => 'Error' } }}
                            </flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <div class="flex gap-3">
        <flux:button variant="primary" wire:click="import" :disabled="! $this->preview || $this->preview['error'] || $this->counts['create'] + $this->counts['update'] === 0">
            Importar {{ $this->counts['create'] + $this->counts['update'] }} contactos
        </flux:button>
        <flux:button :href="route('crm.companies.show', $company)" wire:navigate>Cancelar</flux:button>
    </div>
</div>
