<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappScript extends Model
{
    protected $table = 'oc_whatsapp_scripts';

    protected $fillable = ['title', 'messages', 'sort_order'];

    protected $casts = [
        'messages' => 'array',
    ];
}
