<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class Frase extends Model
{
    protected $table = 'oc_phrases';

    protected $fillable = ['title', 'items', 'sort_order'];

    protected $casts = [
        'items' => 'array',
    ];
}
