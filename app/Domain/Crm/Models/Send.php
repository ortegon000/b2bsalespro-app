<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\SendStatus;
use Database\Factories\Crm\SendFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $sequence_step_id
 * @property Carbon $scheduled_for
 * @property SendStatus $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $opened_at
 * @property Carbon|null $clicked_at
 * @property int $opens_count
 * @property int $clicks_count
 * @property string|null $brevo_message_id
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(SendFactory::class)]
class Send extends Model
{
    /** @use HasFactory<SendFactory> */
    use HasFactory;

    protected $table = 'crm_sends';

    protected $fillable = [
        'subscription_id', 'sequence_step_id', 'scheduled_for', 'status', 'sent_at', 'brevo_message_id', 'error',
        'delivered_at', 'opened_at', 'clicked_at', 'opens_count', 'clicks_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SendStatus::class,
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<SequenceStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(SequenceStep::class, 'sequence_step_id');
    }
}
