<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\TeamRole;
use App\Models\User;
use Database\Factories\Crm\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property TeamRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(TeamMemberFactory::class)]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    protected $table = 'crm_team_members';

    protected $fillable = ['user_id', 'role'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
