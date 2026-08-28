<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class Cierre extends Model
{
    protected $table = 'cierres';

    protected $fillable = ['objecion', 'nombre', 'script', 'usar', 'no_usar', 'orden'];
}
