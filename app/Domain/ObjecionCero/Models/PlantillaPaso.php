<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaPaso extends Model
{
    protected $table = 'plantilla_pasos';

    protected $fillable = ['paso', 'campos', 'orden'];

    protected $casts = [
        'campos' => 'array',
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(PlantillaRespuesta::class);
    }
}
