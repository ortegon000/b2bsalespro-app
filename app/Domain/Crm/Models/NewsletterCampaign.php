<?php

namespace App\Domain\Crm\Models;

use App\Models\User;
use Database\Factories\Crm\NewsletterCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $brevo_template_id
 * @property int $brevo_campaign_id
 * @property Carbon|null $scheduled_for
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(NewsletterCampaignFactory::class)]
class NewsletterCampaign extends Model
{
    /** @use HasFactory<NewsletterCampaignFactory> */
    use HasFactory;

    protected $table = 'crm_newsletter_campaigns';

    protected $fillable = ['name', 'brevo_template_id', 'brevo_campaign_id', 'scheduled_for', 'user_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
