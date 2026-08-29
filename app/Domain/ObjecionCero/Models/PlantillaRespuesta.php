<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaRespuesta extends Model
{
    protected $table = 'oc_template_answers';

    protected $fillable = ['user_id', 'template_step_id', 'field_index', 'value'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PlantillaPaso, $this>
     */
    public function paso(): BelongsTo
    {
        return $this->belongsTo(PlantillaPaso::class, 'template_step_id');
    }
}
