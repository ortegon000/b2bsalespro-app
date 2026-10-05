<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\CourseModality;
use Carbon\CarbonImmutable;
use Database\Factories\Crm\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $sequence_id
 * @property string $title
 * @property CourseModality $modality
 * @property int|null $hours
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon|null $delivered_at
 * @property Carbon|null $reinforcement_starts_on
 * @property Carbon|null $reinforcement_activated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(CourseFactory::class)]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $table = 'crm_courses';

    protected $fillable = [
        'company_id', 'sequence_id', 'title', 'modality', 'hours', 'starts_on', 'ends_on',
        'delivered_at', 'reinforcement_starts_on', 'reinforcement_activated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modality' => CourseModality::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'delivered_at' => 'datetime',
            'reinforcement_starts_on' => 'date',
            'reinforcement_activated_at' => 'datetime',
        ];
    }

    /**
     * Momento (en UTC) en que sale el correo del día indicado de la secuencia,
     * a la hora configurada en la zona horaria del negocio.
     */
    public function sendTimeForDay(int $day): CarbonImmutable
    {
        return CarbonImmutable::parse($this->reinforcement_starts_on?->toDateString() ?? 'today', config('crm.timezone'))
            ->addDays($day - 1)
            ->setTime((int) config('crm.send_hour'), 0)
            ->utc();
    }

    public function isReinforcementActive(): bool
    {
        return $this->reinforcement_activated_at !== null;
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Sequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    /**
     * Contactos inscritos (los que tomaron el curso).
     *
     * @return BelongsToMany<Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'crm_contact_course')->withTimestamps();
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
