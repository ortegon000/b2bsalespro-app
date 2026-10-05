<?php

use App\Domain\Crm\Actions\ActivateReinforcement;
use App\Domain\Crm\Actions\EnrollContacts;
use App\Domain\Crm\Actions\MarkCourseDelivered;
use App\Domain\Crm\Actions\PauseSubscription;
use App\Domain\Crm\Actions\ResumeSubscription;
use App\Domain\Crm\Actions\RetryFailedSends;
use App\Domain\Crm\Actions\SendMissedEmails;
use App\Domain\Crm\Actions\SyncNewsletter;
use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\Subscription;
use App\Domain\Crm\Services\SendMetrics;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Curso')] class extends Component {
    public Course $course;

    public string $startsOn = '';

    /** @var list<int> */
    public array $selectedContacts = [];

    public function mount(Course $course): void
    {
        $this->course = $course->load(['company', 'sequence']);
        $this->startsOn = CarbonImmutable::now(config('crm.timezone'))->next('monday')->toDateString();
    }

    public function markDelivered(MarkCourseDelivered $markCourseDelivered): void
    {
        $markCourseDelivered->handle($this->course);

        $this->course->refresh();
        Flux::toast(variant: 'success', text: 'Curso marcado como impartido.');
    }

    public function syncNewsletter(SyncNewsletter $syncNewsletter): void
    {
        $count = $syncNewsletter->handle($this->course);

        Flux::toast(
            variant: $count > 0 ? 'success' : 'warning',
            text: $count > 0 ? "{$count} contactos se están sumando al newsletter." : 'No hay contactos pendientes (o Brevo no está configurado).',
        );
    }

    public function openEnroll(): void
    {
        $this->reset('selectedContacts');
        Flux::modal('enroll')->show();
    }

    public function enroll(EnrollContacts $enrollContacts): void
    {
        $this->validate(['selectedContacts' => ['required', 'array', 'min:1']], [
            'selectedContacts.required' => 'Elige al menos un contacto.',
            'selectedContacts.min' => 'Elige al menos un contacto.',
        ]);

        $count = $enrollContacts->handle($this->course, array_map('intval', $this->selectedContacts));

        $this->reset('selectedContacts');
        Flux::modal('enroll')->close();
        Flux::toast(variant: 'success', text: "{$count} contactos inscritos.");
    }

    public function unenroll(int $contactId): void
    {
        // Con el refuerzo activo los envíos ya están programados: ahí se pausa, no se quita.
        if ($this->course->isReinforcementActive()) {
            return;
        }

        $this->course->contacts()->detach($contactId);
    }

    public function openActivate(): void
    {
        $this->resetValidation();
        Flux::modal('activate')->show();
    }

    public function activate(ActivateReinforcement $activateReinforcement): void
    {
        $this->validate(['startsOn' => ['required', 'date']], ['startsOn.required' => 'Elige la fecha de inicio.']);

        $count = $activateReinforcement->handle($this->course, $this->startsOn);

        $this->course->refresh();
        Flux::modal('activate')->close();
        Flux::toast(variant: 'success', text: "Refuerzo activado para {$count} contactos.");
    }

    public function pause(int $subscriptionId, PauseSubscription $pauseSubscription): void
    {
        $pauseSubscription->handle($this->course->subscriptions()->findOrFail($subscriptionId));
    }

    public function resume(int $subscriptionId, ResumeSubscription $resumeSubscription): void
    {
        $resumeSubscription->handle($this->course->subscriptions()->findOrFail($subscriptionId));
    }

    public function sendMissed(int $subscriptionId, SendMissedEmails $sendMissedEmails): void
    {
        $count = $sendMissedEmails->handle($this->course->subscriptions()->with('contact')->findOrFail($subscriptionId));

        Flux::toast(variant: 'success', text: "{$count} correos reprogramados (uno por minuto).");
    }

    public function sendMissedToAll(SendMissedEmails $sendMissedEmails): void
    {
        $count = $this->course->subscriptions()->with('contact')->get()
            ->sum(fn (Subscription $subscription) => $sendMissedEmails->handle($subscription));

        Flux::toast(variant: 'success', text: "{$count} correos reprogramados (uno por minuto por persona).");
    }

    public function retryFailed(int $subscriptionId, RetryFailedSends $retryFailedSends): void
    {
        $retryFailedSends->handle($this->course->subscriptions()->findOrFail($subscriptionId));
    }

    public function with(SendMetrics $sendMetrics): array
    {
        $contacts = $this->course->contacts()->orderBy('name')->get();

        $subscriptions = $this->course->subscriptions()
            ->withCount([
                'sends as total_count',
                'sends as sent_count' => fn ($query) => $query->where('status', SendStatus::Sent),
                'sends as skipped_count' => fn ($query) => $query->where('status', SendStatus::Skipped),
                'sends as failed_count' => fn ($query) => $query->where('status', SendStatus::Failed),
                'sends as opened_count' => fn ($query) => $query->whereNotNull('opened_at'),
                'sends as clicked_count' => fn ($query) => $query->whereNotNull('clicked_at'),
            ])
            ->get()
            ->keyBy('contact_id');

        $enrolledIds = $contacts->modelKeys();

        $metrics = $sendMetrics->summary(Send::query()->whereRelation('subscription', 'course_id', $this->course->id));
        $metrics += [
            'delivered_rate' => $sendMetrics->rate($metrics['delivered'], $metrics['sent']),
            'opened_rate' => $sendMetrics->rate($metrics['opened'], $metrics['sent']),
            'clicked_rate' => $sendMetrics->rate($metrics['clicked'], $metrics['sent']),
        ];

        return [
            'contacts' => $contacts,
            'metrics' => $metrics,
            'subscriptions' => $subscriptions,
            'available' => $this->course->company->contacts()->whereNotIn('id', $enrolledIds)->orderBy('name')->get(),
            'sequenceReady' => $this->course->sequence?->isReady() ?? false,
            'hasSkipped' => $subscriptions->sum('skipped_count') > 0,
        ];
    }
}; ?>

<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $course->title }}</flux:heading>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <flux:link :href="route('crm.companies.show', $course->company)" wire:navigate>{{ $course->company->name }}</flux:link>
                <flux:text>· {{ $course->modality->label() }}@if ($course->hours) · {{ $course->hours }} h @endif</flux:text>
                @if ($course->starts_on)<flux:text>· {{ $course->starts_on->format('d/m/Y') }}@if ($course->ends_on) – {{ $course->ends_on->format('d/m/Y') }}@endif</flux:text>@endif
                @if ($course->delivered_at)<flux:badge size="sm" color="green">Impartido</flux:badge>@endif
                @if ($course->isReinforcementActive())<flux:badge size="sm" color="blue">Refuerzo desde {{ $course->reinforcement_starts_on->format('d/m/Y') }}</flux:badge>@endif
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-left" :href="route('crm.companies.show', $course->company)" wire:navigate>Empresa</flux:button>
            <flux:button icon="pencil-square" :href="route('crm.courses.edit', $course)" wire:navigate>Editar</flux:button>
            @if ($course->delivered_at)
                <flux:button icon="newspaper" wire:click="syncNewsletter">Sumar al newsletter</flux:button>
            @endif
            @unless ($course->delivered_at)
                <flux:button icon="check" wire:click="markDelivered" wire:confirm="¿Marcar el curso como impartido? Si la empresa sigue en una etapa abierta, pasará a la primera etapa ganada.">Marcar impartido</flux:button>
            @endunless
            @unless ($course->isReinforcementActive())
                <flux:button variant="primary" icon="envelope" wire:click="openActivate">Activar refuerzo</flux:button>
            @endunless
        </div>
    </div>

    @unless ($course->isReinforcementActive())
        @if (! $sequenceReady)
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>La secuencia no está lista</flux:callout.heading>
                <flux:callout.text>
                    @if ($course->sequence)
                        Faltan plantillas de Brevo en algunos días.
                        <flux:callout.link :href="route('crm.sequences.edit', $course->sequence)" wire:navigate>Configurar secuencia</flux:callout.link>
                    @else
                        El curso no tiene secuencia de refuerzo. Elige una al editarlo.
                    @endif
                </flux:callout.text>
            </flux:callout>
        @endif
    @endunless

    @if ($course->isReinforcementActive() && $metrics['sent'] > 0)
        <section class="flex flex-col gap-3">
            <flux:heading size="lg">Resultados del refuerzo</flux:heading>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Enviados', $metrics['sent'], null],
                    ['Entregados', $metrics['delivered'], $metrics['delivered_rate']],
                    ['Abiertos', $metrics['opened'], $metrics['opened_rate']],
                    ['Con clic', $metrics['clicked'], $metrics['clicked_rate']],
                ] as [$label, $count, $rate])
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>
                        <div class="text-2xl font-semibold">{{ $count }}@if ($rate !== null)<span class="ms-1 text-sm font-normal text-zinc-500 dark:text-zinc-400">{{ $rate }}%</span>@endif</div>
                    </div>
                @endforeach
            </div>
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                Porcentajes sobre los correos enviados. Las aperturas pueden estar infladas por la protección de privacidad de Apple Mail; el clic es la señal más confiable.
                @if ($metrics['failed'] > 0) {{ $metrics['failed'] }} envíos fallaron: puedes reintentarlos por persona. @endif
            </flux:text>
        </section>
    @endif

    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Inscritos ({{ $contacts->count() }})</flux:heading>
            <div class="flex flex-wrap gap-2">
                @if ($hasSkipped)
                    <flux:button size="sm" icon="paper-airplane" wire:click="sendMissedToAll" wire:confirm="Se reprogramarán los correos omitidos de todos los inscritos activos, uno por minuto por persona. ¿Continuar?">Enviar anteriores a todos</flux:button>
                @endif
                <flux:button size="sm" icon="arrow-up-tray" :href="route('crm.companies.contacts.import', ['company' => $course->company, 'curso' => $course->id])" wire:navigate>Importar CSV e inscribir</flux:button>
                <flux:button size="sm" icon="plus" wire:click="openEnroll">Inscribir contactos</flux:button>
            </div>
        </div>

        @forelse ($contacts as $contact)
            @php($subscription = $subscriptions->get($contact->id))
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="enrolled-{{ $contact->id }}">
                <div class="min-w-0">
                    <flux:text variant="strong">{{ $contact->name }}</flux:text>
                    <flux:text class="break-all text-zinc-500 dark:text-zinc-400">{{ $contact->email }}</flux:text>

                    @if ($subscription)
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" :color="match ($subscription->status) { SubscriptionStatus::Active => 'green', SubscriptionStatus::Paused => 'amber', default => 'red' }">{{ $subscription->status->label() }}</flux:badge>
                            <flux:text size="sm">{{ $subscription->sent_count }}/{{ $subscription->total_count }} enviados</flux:text>
                            @if ($subscription->sent_count)<flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">· {{ trans_choice('{1} :count abierto|[0,*] :count abiertos', $subscription->opened_count) }} · {{ trans_choice('{1} :count clic|[0,*] :count clics', $subscription->clicked_count) }}</flux:text>@endif
                            @if ($subscription->skipped_count)<flux:badge size="sm" color="amber">{{ $subscription->skipped_count }} omitidos</flux:badge>@endif
                            @if ($subscription->failed_count)<flux:badge size="sm" color="red">{{ $subscription->failed_count }} fallidos</flux:badge>@endif
                        </div>
                    @elseif ($course->isReinforcementActive())
                        <flux:text size="sm" class="mt-2 text-zinc-500">Sin programar: {{ $contact->unsubscribed_at ? 'se dio de baja' : 'correo rebotado' }}.</flux:text>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap gap-1">
                    @if ($subscription)
                        @if ($subscription->status === SubscriptionStatus::Active)
                            <flux:button size="sm" variant="ghost" icon="pause" wire:click="pause({{ $subscription->id }})">Pausar</flux:button>
                        @elseif ($subscription->status === SubscriptionStatus::Paused)
                            <flux:button size="sm" variant="ghost" icon="play" wire:click="resume({{ $subscription->id }})">Reanudar</flux:button>
                        @endif
                        @if ($subscription->skipped_count && $subscription->status === SubscriptionStatus::Active)
                            <flux:button size="sm" variant="ghost" icon="paper-airplane" wire:click="sendMissed({{ $subscription->id }})" wire:confirm="Se enviarán los {{ $subscription->skipped_count }} correos omitidos a {{ $contact->name }}, uno por minuto. ¿Continuar?">Enviar anteriores</flux:button>
                        @endif
                        @if ($subscription->failed_count)
                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="retryFailed({{ $subscription->id }})">Reintentar</flux:button>
                        @endif
                    @endif
                    @unless ($course->isReinforcementActive())
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="unenroll({{ $contact->id }})" aria-label="Quitar del curso" />
                    @endunless
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">Todavía no hay inscritos. Inscribe a los contactos que tomaron el curso.</flux:text>
        @endforelse
    </section>

    <flux:modal name="enroll" class="w-full max-w-md">
        <form wire:submit="enroll" class="flex flex-col gap-4">
            <flux:heading size="lg">Inscribir contactos</flux:heading>

            @if ($available->isEmpty())
                <flux:text>Todos los contactos de la empresa ya están inscritos. Agrega más desde la ficha de la empresa o importa un CSV.</flux:text>
            @else
                <flux:checkbox.group wire:model="selectedContacts" class="flex flex-col gap-2">
                    @foreach ($available as $contact)
                        <flux:checkbox :value="$contact->id" :label="$contact->name.' · '.$contact->email" wire:key="avail-{{ $contact->id }}" />
                    @endforeach
                </flux:checkbox.group>
                <flux:error name="selectedContacts" />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button>Cancelar</flux:button></flux:modal.close>
                @if ($available->isNotEmpty())<flux:button type="submit" variant="primary">Inscribir</flux:button>@endif
            </div>
        </form>
    </flux:modal>

    <flux:modal name="activate" class="w-full max-w-md">
        <form wire:submit="activate" class="flex flex-col gap-4">
            <flux:heading size="lg">Activar refuerzo de 30 días</flux:heading>
            <flux:text>
                Se programan los 30 correos para los {{ $contacts->count() }} inscritos, uno por día a las {{ config('crm.send_hour') }}:00 (hora de {{ config('crm.timezone') }}), a partir de la fecha elegida. Después de activarlo ya no se puede quitar inscritos, solo pausar.
            </flux:text>
            <flux:input wire:model="startsOn" type="date" label="Primer correo (día 1)" />
            <flux:error name="startsOn" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button>Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Activar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
