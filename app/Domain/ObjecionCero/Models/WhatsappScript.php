<?php

namespace App\Domain\ObjecionCero\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappScript extends Model
{
    protected $table = 'whatsapp_scripts';

    protected $fillable = ['titulo', 'mensajes', 'orden'];

    protected $casts = [
        'mensajes' => 'array',
    ];
}
