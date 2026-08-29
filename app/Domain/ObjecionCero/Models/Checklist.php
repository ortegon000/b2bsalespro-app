<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use SoftDeletes;

    protected $table = 'oc_checklists';

    protected $fillable = ['slug', 'title', 'subtitle', 'note', 'blocks', 'sort_order'];

    protected $casts = [
        'blocks' => 'array',
    ];

    /**
     * @return HasMany<ChecklistProgress, $this>
     */
    public function progreso(): HasMany
    {
        return $this->hasMany(ChecklistProgress::class);
    }
}
