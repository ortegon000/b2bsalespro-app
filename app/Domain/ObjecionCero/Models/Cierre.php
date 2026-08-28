<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class Cierre extends Model
{
    protected $table = 'closings';

    protected $fillable = ['objection', 'name', 'script', 'usage', 'avoid', 'sort_order'];
}
