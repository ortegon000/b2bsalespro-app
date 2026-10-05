<?php

use App\Domain\Crm\Actions\CreateNewsletterCampaign;
use App\Domain\Crm\Actions\SyncCampaignStats;
use App\Domain\Crm\Actions\SyncNewsletter;
use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\NewsletterCampaign;
use App\Domain\Crm\Services\BrevoClient;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Newsletter')] class extends Component {
    public string $name = '';

    public ?int $templateId = null;

    public string $scheduledAt = '';

    public function sync(SyncNewsletter $syncNewsletter): void
    {
        $count = $syncNewsletter->handle();

        Flux::toast(
            variant: $count > 0 ? 'success' : 'warning',
            text: $count > 0 ? "{$count} contactos se están sumando a la lista." : 'No hay contactos pendientes (o Brevo no está configurado).',
        );
    }

    public function refreshStats(SyncCampaignStats $syncCampaignStats, ?int $campaignId = null): void
    {
        $updated = $syncCampaignStats->handle($campaignId ? NewsletterCampaign::findOrFail($campaignId) : null);

        Flux::toast(
            variant: $updated > 0 ? 'success' : 'warning',
            text: $updated > 0 ? trans_choice('{1} 1 campaña actualizada|[2,*] :count campañas actualizadas', $updated) : 'No se pudo actualizar (¿Brevo está configurado?).',
        );
    }

    public function sendCampaign(CreateNewsletterCampaign $createCampaign): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'templateId' => ['required', 'integer', 'min:1'],
            'scheduledAt' => ['nullable', 'date'],
        ], [
            'name.required' => 'Ponle un nombre a la campaña.',
            'templateId.required' => 'Indica el ID de la plantilla de Brevo.',
        ]);

        $scheduledFor = $validated['scheduledAt'] !== ''
            ? CarbonImmutable::parse($validated['scheduledAt'], config('crm.timezone'))->utc()
            : null;

        if ($scheduledFor !== null && $scheduledFor->lt(now()->addMinutes(10))) {
            $this->addError('scheduledAt', 'La fecha de envío debe ser al menos 10 minutos en el futuro.');

            return;
        }

        try {
            $createCampaign->handle($validated['name'], $validated['templateId'], $scheduledFor, auth()->user());
        } catch (BrevoException $exception) {
            $this->addError('campaign', $exception->getMessage());

            return;
        }

        $this->reset('name', 'templateId', 'scheduledAt');

        Flux::toast(variant: 'success', text: $scheduledFor ? 'Campaña programada en Brevo.' : 'Campaña enviada.');
    }

    public function with(SyncNewsletter $syncNewsletter, BrevoClient $brevo): array
    {
        return [
            'configured' => $brevo->isConfigured(),
            'list' => config('crm.brevo.newsletter_list'),
            'pending' => $syncNewsletter->pendingQuery()->count(),
            'synced' => Contact::whereNotNull('newsletter_synced_at')->count(),
            'campaigns' => NewsletterCampaign::with('author')->latest()->latest('id')->limit(20)->get(),
        ];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-8">
    <div>
        <flux:heading size="xl">Newsletter</flux:heading>
        <flux:subheading>Lista «{{ $list }}» de Brevo: quienes toman un curso se suman solos al marcarlo como impartido.</flux:subheading>
    </div>

    @unless ($configured)
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Brevo no está configurado</flux:callout.heading>
            <flux:callout.text>Define BREVO_API_KEY en el .env para sincronizar contactos y enviar campañas.</flux:callout.text>
        </flux:callout>
    @endunless

    <section class="flex flex-col gap-3">
        <flux:heading size="lg">Contactos</flux:heading>
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="green">{{ trans_choice('{1} :count contacto en la lista|[0,*] :count contactos en la lista', $synced) }}</flux:badge>
            <flux:badge :color="$pending > 0 ? 'amber' : 'zinc'">{{ trans_choice('{1} :count pendiente de sumar|[0,*] :count pendientes de sumar', $pending) }}</flux:badge>
        </div>
        <flux:text>Los pendientes son quienes tomaron un curso impartido, pueden recibir correo y aún no se suman a la lista.</flux:text>
        <div><flux:button icon="arrow-path" wire:click="sync" :disabled="! $configured || $pending === 0">Sincronizar pendientes</flux:button></div>
    </section>

    <section class="flex flex-col gap-3">
        <flux:heading size="lg">Enviar una campaña</flux:heading>
        <flux:text>Crea en Brevo una campaña con una de tus plantillas para toda la lista. El asunto y el remitente salen de la plantilla.</flux:text>

        <form wire:submit="sendCampaign" class="flex flex-col gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:input wire:model="name" label="Nombre de la campaña" placeholder="Newsletter de octubre" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="templateId" type="number" min="1" label="ID de la plantilla de Brevo" />
                <flux:input wire:model="scheduledAt" type="datetime-local" label="Programar para (opcional)" description="Hora de {{ config('crm.timezone') }}. Vacío = enviar ahora." />
            </div>
            <flux:error name="campaign" />
            <div>
                <flux:button type="submit" variant="primary" icon="paper-airplane" :disabled="! $configured" wire:confirm="Se enviará a todos los suscriptores de la lista «{{ $list }}» en Brevo. ¿Continuar?">
                    {{ $scheduledAt !== '' ? 'Programar campaña' : 'Enviar ahora' }}
                </flux:button>
            </div>
        </form>
    </section>

    <section class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Campañas</flux:heading>
            @if ($campaigns->isNotEmpty())
                <flux:button size="sm" icon="arrow-path" wire:click="refreshStats" :disabled="! $configured">Actualizar métricas</flux:button>
            @endif
        </div>

        @forelse ($campaigns as $campaign)
            <div class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="campaign-{{ $campaign->id }}">
                <div class="min-w-0">
                    <flux:text variant="strong">{{ $campaign->name }}</flux:text>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        Plantilla {{ $campaign->brevo_template_id }} · Campaña {{ $campaign->brevo_campaign_id }}@if ($campaign->author) · {{ $campaign->author->name }}@endif
                    </flux:text>
                </div>
                @if ($campaign->stats)
                    @php($stats = $campaign->stats)
                    <div class="flex w-full flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                        <span>{{ $stats['sent'] }} enviados</span>
                        <span>{{ $stats['delivered'] }} entregados</span>
                        <span>{{ $stats['opened'] }} abiertos{{ $stats['delivered'] > 0 ? ' ('.round($stats['opened'] / $stats['delivered'] * 100).'%)' : '' }}</span>
                        <span>{{ $stats['clicked'] }} con clic{{ $stats['delivered'] > 0 ? ' ('.round($stats['clicked'] / $stats['delivered'] * 100).'%)' : '' }}</span>
                        <span>{{ $stats['unsubscribed'] }} bajas</span>
                        <span>{{ $stats['bounced'] }} rebotes</span>
                        <span class="text-zinc-500">actualizado {{ $campaign->stats_synced_at?->diffForHumans() }}</span>
                    </div>
                @endif
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <flux:badge size="sm" :color="$campaign->scheduled_for ? 'blue' : 'green'">
                        {{ $campaign->scheduled_for ? 'Programada '.$campaign->scheduled_for->timezone(config('crm.timezone'))->format('d/m/Y H:i') : 'Enviada' }}
                    </flux:badge>
                    <flux:text size="sm" class="text-zinc-500">{{ $campaign->created_at->timezone(config('crm.timezone'))->format('d/m/Y H:i') }}</flux:text>
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">Todavía no se ha enviado ninguna campaña desde el CRM.</flux:text>
        @endforelse
    </section>
</div>
