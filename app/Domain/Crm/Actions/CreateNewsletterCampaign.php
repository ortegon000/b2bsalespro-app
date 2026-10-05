<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Exceptions\BrevoException;
use App\Domain\Crm\Models\NewsletterCampaign;
use App\Domain\Crm\Services\BrevoClient;
use App\Models\User;
use Carbon\CarbonImmutable;

class CreateNewsletterCampaign
{
    public function __construct(private BrevoClient $brevo) {}

    /**
     * Crea en Brevo una campaña con la plantilla indicada para la lista del newsletter y la envía
     * de inmediato o la deja programada. Queda registrada en el CRM.
     *
     * @throws BrevoException
     */
    public function handle(string $name, int $templateId, ?CarbonImmutable $scheduledFor, User $author): NewsletterCampaign
    {
        $campaignId = $this->brevo->createCampaign($name, $templateId, $this->brevo->newsletterListId(), $scheduledFor);

        if ($scheduledFor === null) {
            $this->brevo->sendCampaignNow($campaignId);
        }

        return NewsletterCampaign::create([
            'name' => $name,
            'brevo_template_id' => $templateId,
            'brevo_campaign_id' => $campaignId,
            'scheduled_for' => $scheduledFor,
            'user_id' => $author->id,
        ]);
    }
}
