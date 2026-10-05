<?php

namespace App\Domain\Crm\Models;

use Database\Factories\Crm\SequenceFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(SequenceFactory::class)]
class Sequence extends Model
{
    /** @use HasFactory<SequenceFactory> */
    use HasFactory;

    protected $table = 'crm_sequences';

    protected $fillable = ['name'];

    /**
     * @return HasMany<SequenceStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SequenceStep::class)->orderBy('day');
    }

    /**
     * Una secuencia solo se puede programar cuando todos sus pasos tienen plantilla de Brevo.
     */
    public function isReady(): bool
    {
        return $this->steps()->exists() && ! $this->steps()->whereNull('brevo_template_id')->exists();
    }
}
