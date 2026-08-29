<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $visitas Alias agregado por ObjecionCeroUsageReport::handle() vía selectRaw(); no existe en la tabla.
 * @property-read int $usuarios Alias agregado por ObjecionCeroUsageReport::handle() vía selectRaw(); no existe en la tabla.
 * @property-read int $aperturas Alias agregado por ObjecionCeroUsageReport::handle() vía selectRaw(); no existe en la tabla.
 */
class ContentView extends Model
{
    protected $table = 'oc_content_views';

    protected $fillable = ['user_id', 'section', 'viewable_type', 'viewable_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $section, ?string $viewableType = null, ?int $viewableId = null): void
    {
        if (! auth()->check()) {
            return;
        }

        static::create([
            'user_id' => auth()->id(),
            'section' => $section,
            'viewable_type' => $viewableType,
            'viewable_id' => $viewableId,
        ]);
    }
}
