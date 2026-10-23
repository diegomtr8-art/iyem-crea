<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_log';

    // Los únicos valores que acepta la columna `accion` (enum en la migración)
    public const ACCIONES = ['created', 'updated', 'deleted'];

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

    // La bitácora solo crece: ninguna fila se edita ni se borra desde el código
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('La bitácora de auditoría no se puede modificar.');
        });

        static::deleting(function () {
            throw new LogicException('La bitácora de auditoría no se puede borrar.');
        });
    }

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
        // Con otro valor, MySQL en modo estricto rechaza el insert y la operación termina en error 500
        if (! in_array($accion, self::ACCIONES, true)) {
            throw new InvalidArgumentException("Acción de auditoría no válida: {$accion}. Usa created, updated o deleted.");
        }

        $user = auth()->user();

        self::create([
            'entidad'          => $entidad,
            'entidad_id'       => $entidadId,
            'accion'           => $accion,
            'campo_modificado' => $campo,
            'valor_anterior'   => self::comoTexto($anterior),
            'valor_nuevo'      => self::comoTexto($nuevo),
            'usuario_id'       => $user?->id,
            'usuario_nombre'   => $user?->name,
            'ip_address'       => request()->ip(),
            'descripcion'      => $descripcion,
        ]);
    }

    /** $1,234.56: el formato de los montos en las descripciones. */
    public static function dinero(mixed $monto): string
    {
        return '$' . number_format((float) $monto, 2);
    }

    private static function comoTexto(mixed $valor): ?string
    {
        return match (true) {
            $valor === null                     => null,
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d'),
            is_bool($valor)                     => $valor ? 'Sí' : 'No',
            default                             => (string) $valor,
        };
    }
}
