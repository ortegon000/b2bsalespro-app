<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntaGrupo extends Model
{
    protected $table = 'oc_question_groups';

    protected $fillable = ['title', 'items', 'sort_order'];

    protected $casts = [
        'items' => 'array',
    ];
}
