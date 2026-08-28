<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categories';

    protected $fillable = ['slug', 'label', 'icon'];

    public function fichas(): HasMany
    {
        return $this->hasMany(Ficha::class, 'category_id');
    }
}
