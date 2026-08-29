<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistProgress extends Model
{
    protected $table = 'oc_checklist_progress';

    protected $fillable = ['user_id', 'checklist_id', 'item_key', 'checked_at'];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Checklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}
