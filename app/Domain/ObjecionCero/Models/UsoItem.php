<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class UsoItem extends Model
{
    protected $table = 'usage_items';

    protected $fillable = ['title', 'description', 'sort_order'];
}
