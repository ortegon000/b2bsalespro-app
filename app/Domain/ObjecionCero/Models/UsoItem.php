<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class UsoItem extends Model
{
    protected $table = 'oc_usage_items';

    protected $fillable = ['title', 'description', 'sort_order'];
}
