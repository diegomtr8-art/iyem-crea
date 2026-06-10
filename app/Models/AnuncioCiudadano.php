<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnuncioCiudadano extends Model
{
    protected $table = 'anuncios_ciudadano';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensaje',
        'tipo',
        'leido',
        'url_accion',
    ];

    protected $casts = [
        'leido' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function paraUsuario(int $userId)
    {
        return static::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        })->orderByDesc('created_at');
    }
}
