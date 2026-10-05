<?php

namespace App\Domain\Crm\Models;

use Database\Factories\Crm\SequenceStepFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sequence_id
 * @property int $day
 * @property int|null $brevo_template_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(SequenceStepFactory::class)]
class SequenceStep extends Model
{
    /** @use HasFactory<SequenceStepFactory> */
    use HasFactory;

    protected $table = 'crm_sequence_steps';

    protected $fillable = ['sequence_id', 'day', 'brevo_template_id'];

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }
}
