<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\NewsletterCampaign;
use App\Domain\Crm\Services\BrevoClient;

class SyncCampaignStats
{
    /**
     * Días desde el envío durante los que se siguen actualizando las métricas de una campaña.
     */
    public const int TRACKING_DAYS = 30;

    public function __construct(private BrevoClient $brevo) {}

    /**
     * Trae de Brevo las estadísticas de una campaña, o de las enviadas en los últimos 30 días.
     * Si una campaña falla se sigue con las demás; sin la API configurada no hace nada.
     *
     * @return int Cantidad de campañas actualizadas
     */
    public function handle(?NewsletterCampaign $campaign = null): int
    {
        if (! $this->brevo->isConfigured()) {
            return 0;
        }

        $campaigns = $campaign
            ? collect([$campaign])
            : NewsletterCampaign::query()
                ->where(fn ($query) => $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
                ->where('created_at', '>=', now()->subDays(self::TRACKING_DAYS))
                ->get();

        $updated = 0;

        foreach ($campaigns as $item) {
            try {
                $item->update(['stats' => $this->brevo->campaignStats($item->brevo_campaign_id), 'stats_synced_at' => now()]);
                $updated++;
            } catch (BrevoException) {
                continue;
            }
        }

        return $updated;
    }
}
