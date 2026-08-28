<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntaGrupo extends Model
{
    protected $table = 'pregunta_grupos';

    protected $fillable = ['titulo', 'items', 'orden'];

    protected $casts = [
        'items' => 'array',
    ];
}
