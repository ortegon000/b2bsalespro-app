<?php

namespace App\Domain\ObjecionCero\Models;

use App\Domain\ObjecionCero\Enums\TipoObjecion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ficha extends Model
{
    protected $table = 'fichas';

    protected $fillable = [
        'numero', 'categoria_id', 'tipo', 'objecion', 'confirmar', 'significa',
        'peor', 'dialogo', 'pregunta', 'cierre', 'error', 'consejo', 'ramas',
    ];

    protected $casts = [
        'tipo' => TipoObjecion::class,
        'peor' => 'array',
        'dialogo' => 'array',
        'ramas' => 'array',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
