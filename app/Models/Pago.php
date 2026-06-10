<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'credito_id',
        'acreditado_id',
        'folio',
        'monto_recibido',
        'aplicado_mora',
        'aplicado_ordinario',
        'aplicado_capital',
        'cuotas_cubiertas',
        'referencia',
        'forma_pago',
        'fecha_pago',
        'observaciones',
        'registrado_por',
        'cancelado',
        'cancelado_por',
        'cancelado_at',
        'motivo_cancelacion',
    ];

    protected $casts = [
        'cuotas_cubiertas'   => 'array',
        'fecha_pago'         => 'date',
        'monto_recibido'     => 'decimal:2',
        'aplicado_mora'      => 'decimal:2',
        'aplicado_ordinario' => 'decimal:2',
        'aplicado_capital'   => 'decimal:2',
        'cancelado'          => 'boolean',
        'cancelado_at'       => 'datetime',
    ];

    public static function generarFolio(): string
    {
        return DB::transaction(function () {
            $ultimo = static::lockForUpdate()->max('id') ?? 0;
            $numero = str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
            return 'REC-' . $numero . '-' . date('Y');
        });
    }

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function acreditado()
    {
        return $this->belongsTo(Acreditado::class);
    }

    public function cajero()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }

    public function scopeActivos($query)
    {
        return $query->where('cancelado', false);
    }
}
