<?php

namespace App\Domain\ObjecionCero\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['user_id', 'page', 'message'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
