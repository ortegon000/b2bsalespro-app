<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class UsoItem extends Model
{
    protected $table = 'uso_items';

    protected $fillable = ['titulo', 'descripcion', 'orden'];
}
