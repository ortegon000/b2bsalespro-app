<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaRespuesta extends Model
{
    protected $table = 'plantilla_respuestas';

    protected $fillable = ['user_id', 'plantilla_paso_id', 'campo_index', 'value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paso(): BelongsTo
    {
        return $this->belongsTo(PlantillaPaso::class, 'plantilla_paso_id');
    }
}
