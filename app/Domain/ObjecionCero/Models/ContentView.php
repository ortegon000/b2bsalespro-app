<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentView extends Model
{
    protected $table = 'oc_content_views';

    protected $fillable = ['user_id', 'section', 'viewable_type', 'viewable_id'];

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
