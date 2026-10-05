<?php

namespace App\Domain\Crm\Models;

use App\Models\User;
use Database\Factories\Crm\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property bool $is_primary
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $bounced_at
 * @property Carbon|null $newsletter_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ContactFactory::class)]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id', 'user_id', 'name', 'email', 'phone', 'job_title', 'is_primary',
        'unsubscribed_at', 'bounced_at', 'newsletter_synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'unsubscribed_at' => 'datetime',
            'bounced_at' => 'datetime',
            'newsletter_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Se le pueden enviar correos de refuerzo mientras no se haya dado de baja ni rebotado.
     */
    public function canReceiveEmail(): bool
    {
        return $this->unsubscribed_at === null && $this->bounced_at === null;
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'crm_contact_course')->withTimestamps();
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Cuenta de Objeción Cero asociada, cuando el contacto tiene acceso.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
