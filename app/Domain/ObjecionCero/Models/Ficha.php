<?php

namespace App\Domain\ObjecionCero\Models;

use App\Domain\ObjecionCero\Enums\TipoObjecion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ficha extends Model
{
    protected $table = 'oc_objections';

    protected $fillable = [
        'number', 'category_id', 'type', 'objection', 'confirm', 'meaning',
        'worst_case', 'dialogue', 'question', 'closing', 'error', 'tip', 'branches',
    ];

    protected $casts = [
        'type' => TipoObjecion::class,
        'worst_case' => 'array',
        'dialogue' => 'array',
        'branches' => 'array',
    ];

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }
}
