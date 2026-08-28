<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    protected $table = 'checklists';

    protected $fillable = ['slug', 'titulo', 'sub', 'nota', 'bloques', 'orden'];

    protected $casts = [
        'bloques' => 'array',
    ];

    public function progreso(): HasMany
    {
        return $this->hasMany(ChecklistProgress::class);
    }
}
