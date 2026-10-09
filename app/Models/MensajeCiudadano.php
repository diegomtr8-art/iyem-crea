<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensajeCiudadano extends Model
{
    protected $table = 'mensajes_ciudadano';

    protected $fillable = ['user_id', 'asunto', 'mensaje', 'atendido_at', 'atendido_por'];

    protected $casts = [
        'atendido_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereNull('atendido_at');
    }

    public function scopeAtendidos(Builder $query): Builder
    {
        return $query->whereNotNull('atendido_at');
    }
}
