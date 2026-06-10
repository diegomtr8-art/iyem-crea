<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amortizacion extends Model
{
    use HasFactory;

    // Laravel por defecto busca la tabla en plural, 
    // pero tu captura muestra "amortizaciones", así que está bien.
    protected $table = 'amortizaciones';

    protected $fillable = [
        'credito_id',
        'numero_cuota',
        'fecha_vencimiento',
        'saldo_insoluto',
        'capital_esperado',
        'interes_ordinario_esperado',
        'cuota_fija',
        'capital_pagado',
        'interes_ordinario_pagado',
        'interes_moratorio_pagado',
        'interes_moratorio_generado', // <--- DEBE ESTAR AQUÍ
        'comision_pagada',
        'moratorio_acumulado',
        'pago_restante',
        'estado',
        'fecha_ultimo_pago',
    ];

    // Relación inversa: Una amortización pertenece a un crédito
    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }
}