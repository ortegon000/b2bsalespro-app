<?php

use App\Domain\Crm\Actions\ImportCompanies;
use App\Domain\Crm\Services\CompanyCsv;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Importar empresas')] class extends Component {
    use WithFileUploads;

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
        return $this->file ? app(CompanyCsv::class)->parse($this->file->get(), auth()->user()) : null;
    }

    /**
     * @return array{companies_create: int, companies_update: int, contacts_create: int, contacts_update: int, errors: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(CompanyCsv::class)->summary($this->preview['rows'] ?? []);
    }

    public function import(ImportCompanies $importCompanies, CompanyCsv $companyCsv): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        // Se vuelve a leer el archivo subido: no se confía en lo que muestra la vista previa.
        $parsed = $companyCsv->parse($this->file->get(), auth()->user());

        if ($parsed['error'] !== null) {
            $this->addError('file', $parsed['error']);

            return;
        }

        $result = $importCompanies->handle($parsed['rows'], auth()->user());

        Flux::toast(variant: 'success', text: trans_choice('{0} Sin empresas nuevas|{1} 1 empresa nueva|[2,*] :count empresas nuevas', $result['companies_created'])
            .', '.trans_choice('{0} ninguna actualizada|{1} 1 actualizada|[2,*] :count actualizadas', $result['companies_updated'])
            .'; '.trans_choice('{0} sin contactos nuevos|{1} 1 contacto nuevo|[2,*] :count contactos nuevos', $result['contacts_created'])
            .', '.trans_choice('{0} ninguno actualizado|{1} 1 actualizado|[2,*] :count actualizados', $result['contacts_updated']).'.');

        $this->redirectRoute('crm.pipeline', navigate: true);
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Importar empresas</flux:heading>
        <flux:subheading>Empresas con sus contactos, desde un archivo CSV.</flux:subheading>
    </div>

    <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:text>
            1. Descarga la plantilla. Cada fila es una empresa con uno de sus contactos; repite el nombre de la empresa para agregarle más contactos. Solo <strong>empresa</strong> es obligatoria.
        </flux:text>
        <div>
            <flux:button size="sm" icon="arrow-down-tray" :href="route('crm.companies.template')">Descargar plantilla</flux:button>
        </div>
        <flux:text>
            2. <strong>Etapa</strong>: nombre de una etapa del pipeline (vacío = la primera). <strong>Responsable</strong>: email de alguien del equipo (vacío = tú). <strong>Origen</strong>: formulario, WhatsApp, landing, sitio web, importación o manual (vacío = importación).
        </flux:text>
        <flux:text>
            3. Si la empresa ya existe (mismo nombre, sin importar mayúsculas ni acentos) se actualiza solo lo que traiga lleno el archivo. Un contacto cuyo email ya pertenece a otra empresa se omite.
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
                <flux:badge color="green">{{ trans_choice('{1} :count empresa nueva|[0,*] :count empresas nuevas', $this->summary['companies_create']) }}</flux:badge>
                <flux:badge color="blue">{{ trans_choice('{1} :count empresa a actualizar|[0,*] :count empresas a actualizar', $this->summary['companies_update']) }}</flux:badge>
                <flux:badge color="green">{{ trans_choice('{1} :count contacto nuevo|[0,*] :count contactos nuevos', $this->summary['contacts_create']) }}</flux:badge>
                <flux:badge color="blue">{{ trans_choice('{1} :count contacto a actualizar|[0,*] :count contactos a actualizar', $this->summary['contacts_update']) }}</flux:badge>
                <flux:badge color="red">{{ trans_choice('{1} :count fila con error (se omite)|[0,*] :count filas con error (se omiten)', $this->summary['errors']) }}</flux:badge>
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($this->preview['rows'] as $row)
                    <div class="flex flex-wrap items-start justify-between gap-2 rounded-lg border p-3 {{ $row['status'] === 'error' ? 'border-red-300 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }}" wire:key="row-{{ $row['line'] }}">
                        <div class="min-w-0">
                            <flux:text variant="strong">{{ $row['company']['name'] ?: '—' }}</flux:text>
                            <flux:text class="text-zinc-500 dark:text-zinc-400">
                                {{ $row['company']['action'] === 'create' ? 'Empresa nueva' : 'Empresa existente' }}@if ($row['company']['stage_label']) · {{ $row['company']['stage_label'] }}@endif @if ($row['company']['owner_label']) · {{ $row['company']['owner_label'] }}@endif
                            </flux:text>
                            @if ($row['contact'])
                                <flux:text class="mt-1 break-all">
                                    {{ $row['contact']['name'] }} · {{ $row['contact']['email'] }}@if ($row['contact']['job_title']) · {{ $row['contact']['job_title'] }}@endif
                                </flux:text>
                            @endif
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

    @php($importable = $this->preview && ! $this->preview['error'] && $this->summary['errors'] < count($this->preview['rows']))
    <div class="flex gap-3">
        <flux:button variant="primary" wire:click="import" :disabled="! $importable">Importar</flux:button>
        <flux:button :href="route('crm.pipeline')" wire:navigate>Cancelar</flux:button>
    </div>
</div>
