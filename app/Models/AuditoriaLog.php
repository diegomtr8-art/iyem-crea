<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_log';

    protected $fillable = [
        'entidad',
        'entidad_id',
        'accion',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
        'usuario_id',
        'usuario_nombre',
        'ip_address',
        'descripcion',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public static function registrar(
        string $entidad,
        int $entidadId,
        string $accion,
        ?string $campo = null,
        mixed $anterior = null,
        mixed $nuevo = null,
        ?string $descripcion = null
    ): void {
        $user = auth()->user();

        self::create([
            'entidad'          => $entidad,
            'entidad_id'       => $entidadId,
            'accion'           => $accion,
            'campo_modificado' => $campo,
            'valor_anterior'   => $anterior !== null ? (string) $anterior : null,
            'valor_nuevo'      => $nuevo !== null ? (string) $nuevo : null,
            'usuario_id'       => $user?->id,
            'usuario_nombre'   => $user?->name,
            'ip_address'       => request()->ip(),
            'descripcion'      => $descripcion,
        ]);
    }
}
