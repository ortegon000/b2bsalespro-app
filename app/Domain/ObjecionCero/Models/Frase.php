<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class Frase extends Model
{
    protected $table = 'frases';

    protected $fillable = ['titulo', 'items', 'orden'];

    protected $casts = [
        'items' => 'array',
    ];
}
