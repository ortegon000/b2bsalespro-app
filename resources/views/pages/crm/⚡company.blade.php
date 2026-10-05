<?php

use App\Domain\Crm\Actions\SaveContact;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Models\Company;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Empresa')] class extends Component {
    public Company $company;

    public ?int $contactId = null;
    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactPhone = '';
    public string $contactJobTitle = '';
    public bool $contactIsPrimary = false;

    public string $activityType = 'note';
    public string $activityBody = '';

    public function mount(Company $company): void
    {
        $this->company = $company->load(['stage', 'owner']);
    }

    public function newContact(): void
    {
        $this->resetContactForm();
        Flux::modal('contact-form')->show();
    }

    public function editContact(int $id): void
    {
        $contact = $this->company->contacts()->findOrFail($id);

        $this->resetContactForm();
        $this->contactId = $contact->id;
        $this->contactName = $contact->name;
        $this->contactEmail = $contact->email;
        $this->contactPhone = $contact->phone ?? '';
        $this->contactJobTitle = $contact->job_title ?? '';
        $this->contactIsPrimary = $contact->is_primary;

        Flux::modal('contact-form')->show();
    }

    public function saveContact(SaveContact $saveContact): void
    {
        $validated = $this->validate([
            'contactName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email:rfc', 'max:255', Rule::unique('crm_contacts', 'email')->ignore($this->contactId)],
            'contactPhone' => ['nullable', 'string', 'max:50'],
            'contactJobTitle' => ['nullable', 'string', 'max:255'],
            'contactIsPrimary' => ['boolean'],
        ]);

        $saveContact->handle(
            $this->company,
            [
                'name' => $validated['contactName'],
                'email' => $validated['contactEmail'],
                'phone' => $validated['contactPhone'] ?: null,
                'job_title' => $validated['contactJobTitle'] ?: null,
                'is_primary' => $validated['contactIsPrimary'],
            ],
            $this->contactId ? $this->company->contacts()->findOrFail($this->contactId) : null,
        );

        $this->resetContactForm();
        Flux::modal('contact-form')->close();
        Flux::toast(variant: 'success', text: 'Contacto guardado.');
    }

    public function deleteContact(int $id): void
    {
        $this->company->contacts()->findOrFail($id)->delete();
    }

    public function addActivity(): void
    {
        $validated = $this->validate([
            'activityType' => ['required', Rule::enum(ActivityType::class)],
            'activityBody' => ['required', 'string', 'max:5000'],
        ]);

        $this->company->activities()->create([
            'user_id' => auth()->id(),
            'type' => $validated['activityType'],
            'body' => $validated['activityBody'],
            'occurred_at' => now(),
        ]);

        $this->reset('activityBody');
    }

    public function deleteActivity(int $id): void
    {
        $this->company->activities()->findOrFail($id)->delete();
    }

    public function deleteCompany(): void
    {
        $this->company->delete();

        Flux::toast(variant: 'success', text: 'Empresa eliminada.');

        $this->redirectRoute('crm.pipeline', navigate: true);
    }

    private function resetContactForm(): void
    {
        $this->reset('contactId', 'contactName', 'contactEmail', 'contactPhone', 'contactJobTitle', 'contactIsPrimary');
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'contacts' => $this->company->contacts()->orderByDesc('is_primary')->orderBy('name')->get(),
            'courses' => $this->company->courses()->withCount('contacts')->get(),
            'activities' => $this->company->activities()->with(['author', 'contact'])->orderByDesc('occurred_at')->orderByDesc('id')->get(),
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $company->name }}</flux:heading>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <flux:badge>{{ $company->stage->name }}</flux:badge>
                <flux:text>{{ $company->source->label() }}</flux:text>
                @if ($company->owner)
                    <flux:text>· {{ $company->owner->name }}</flux:text>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            <flux:button icon="arrow-left" :href="route('crm.pipeline')" wire:navigate>Pipeline</flux:button>
            <flux:button icon="pencil-square" :href="route('crm.companies.edit', $company)" wire:navigate>Editar</flux:button>
            <flux:button icon="trash" variant="danger" wire:click="deleteCompany" wire:confirm="¿Eliminar {{ $company->name }} con sus actividades? Los contactos se conservan sin empresa.">Eliminar</flux:button>
        </div>
    </div>

    @if ($company->industry || $company->website || $company->notes || $company->lost_reason)
        <div class="flex flex-col gap-1">
            @if ($company->industry)<flux:text>Giro: {{ $company->industry }}</flux:text>@endif
            @if ($company->website)<flux:text>Sitio: {{ $company->website }}</flux:text>@endif
            @if ($company->lost_reason)<flux:text>Motivo de pérdida: {{ $company->lost_reason }}</flux:text>@endif
            @if ($company->notes)<flux:text class="whitespace-pre-line">{{ $company->notes }}</flux:text>@endif
        </div>
    @endif

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">Contactos</flux:heading>
            <div class="flex gap-2">
                <flux:button size="sm" icon="arrow-up-tray" :href="route('crm.companies.contacts.import', $company)" wire:navigate>Importar CSV</flux:button>
                <flux:button size="sm" icon="plus" wire:click="newContact">Agregar contacto</flux:button>
            </div>
        </div>

        @forelse ($contacts as $contact)
            <div class="flex items-start justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="contact-{{ $contact->id }}">
                <div class="min-w-0">
                    <flux:text variant="strong">
                        {{ $contact->name }}
                        @if ($contact->is_primary)<flux:badge size="sm" class="ms-1">Principal</flux:badge>@endif
                    </flux:text>
                    <flux:text class="break-all text-zinc-500 dark:text-zinc-400">{{ $contact->email }}@if ($contact->phone) · {{ $contact->phone }}@endif</flux:text>
                    @if ($contact->job_title)<flux:text class="text-zinc-500 dark:text-zinc-400">{{ $contact->job_title }}</flux:text>@endif
                </div>
                <div class="flex shrink-0 gap-1">
                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editContact({{ $contact->id }})" aria-label="Editar contacto" />
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteContact({{ $contact->id }})" wire:confirm="¿Eliminar a {{ $contact->name }}?" aria-label="Eliminar contacto" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">Todavía no hay contactos.</flux:text>
        @endforelse
    </section>

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="lg">Cursos</flux:heading>
            <flux:button size="sm" icon="plus" :href="route('crm.companies.courses.create', $company)" wire:navigate>Nuevo curso</flux:button>
        </div>

        @forelse ($courses as $course)
            <a href="{{ route('crm.courses.show', $course) }}" wire:navigate wire:key="course-{{ $course->id }}" class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-zinc-200 p-3 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                <div class="min-w-0">
                    <flux:text variant="strong">{{ $course->title }}</flux:text>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ $course->modality->label() }}@if ($course->hours) · {{ $course->hours }} h @endif · {{ trans_choice('{0} Sin inscritos|{1} 1 inscrito|[2,*] :count inscritos', $course->contacts_count) }}
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    @if ($course->delivered_at)<flux:badge size="sm" color="green">Impartido</flux:badge>@endif
                    @if ($course->isReinforcementActive())<flux:badge size="sm" color="blue">Refuerzo activo</flux:badge>@endif
                </div>
            </a>
        @empty
            <flux:text class="text-zinc-500">Todavía no hay cursos.</flux:text>
        @endforelse
    </section>

    <section class="flex flex-col gap-3">
        <flux:heading size="lg">Actividad</flux:heading>

        <form wire:submit="addActivity" class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:select wire:model="activityType" label="Tipo">
                @foreach (ActivityType::cases() as $type)
                    <flux:select.option :value="$type->value">{{ $type->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="activityBody" label="Detalle" rows="3" />
            <div><flux:button type="submit" variant="primary" size="sm">Registrar</flux:button></div>
        </form>

        @forelse ($activities as $activity)
            <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="activity-{{ $activity->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm">{{ $activity->type->label() }}</flux:badge>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">
                            {{ $activity->occurred_at->timezone(config('crm.timezone'))->format('d/m/Y H:i') }}@if ($activity->author) · {{ $activity->author->name }}@endif
                        </flux:text>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteActivity({{ $activity->id }})" wire:confirm="¿Eliminar esta actividad?" aria-label="Eliminar actividad" />
                </div>
                @if ($activity->body)<flux:text class="mt-2 whitespace-pre-line">{{ $activity->body }}</flux:text>@endif
            </div>
        @empty
            <flux:text class="text-zinc-500">Sin actividad registrada.</flux:text>
        @endforelse
    </section>

    <flux:modal name="contact-form" class="w-full max-w-md">
        <form wire:submit="saveContact" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $contactId ? 'Editar contacto' : 'Nuevo contacto' }}</flux:heading>
            <flux:input wire:model="contactName" label="Nombre" required />
            <flux:input wire:model="contactEmail" label="Email" type="email" required />
            <flux:input wire:model="contactPhone" label="Teléfono" />
            <flux:input wire:model="contactJobTitle" label="Puesto" />
            <flux:checkbox wire:model="contactIsPrimary" label="Contacto principal" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button>Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
