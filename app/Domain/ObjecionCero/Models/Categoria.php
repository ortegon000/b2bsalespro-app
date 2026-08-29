<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'oc_categories';

    protected $fillable = ['slug', 'label', 'icon'];

    /**
     * @return HasMany<Ficha, $this>
     */
    public function fichas(): HasMany
    {
        return $this->hasMany(Ficha::class, 'category_id');
    }
}
