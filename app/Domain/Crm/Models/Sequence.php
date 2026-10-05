<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Services\ReinforcementEmail;
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
     * Una secuencia solo se puede programar cuando todos sus días tienen correo: una vista Blade
     * propia o, mientras dure la transición, una plantilla de Brevo.
     */
    public function isReady(): bool
    {
        $steps = $this->steps()->get();
        $emails = app(ReinforcementEmail::class);

        return $steps->isNotEmpty()
            && $steps->every(fn (SequenceStep $step) => $step->brevo_template_id !== null || $emails->exists($step->day));
    }
}
