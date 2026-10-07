<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeCiudadano extends Model
{
    protected $table = 'mensajes_ciudadano';

    protected $fillable = ['user_id', 'asunto', 'mensaje'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
