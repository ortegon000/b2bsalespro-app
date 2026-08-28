<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaPaso extends Model
{
    use SoftDeletes;

    protected $table = 'template_steps';

    protected $fillable = ['title', 'fields', 'sort_order'];

    protected $casts = [
        'fields' => 'array',
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(PlantillaRespuesta::class, 'template_step_id');
    }
}
