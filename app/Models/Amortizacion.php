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
        'observaciones',
    ];

    // Relación inversa: Una amortización pertenece a un crédito
    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    //Método de casts
    protected function casts(): array 
    {
        return [
            'fecha_vencimiento' => 'date:d/m/Y', //se específica el formato de las fechas
            'saldo_insoluto' => 'decimal:2',
            'capital_esperado' => 'decimal:2',
            'interes_ordinario_esperado' => 'decimal:2',
            'cuota_fija' => 'decimal:2',
            'capital_pagado' => 'decimal:2',
            'interes_ordinario_pagado' => 'decimal:2',
            'interes_moratorio_pagado' => 'decimal:2',
            'interes_moratorio_generado' => 'decimal:2',
            'comision_pagada' => 'decimal:2',
            'moratorio_acumulado' => 'decimal:2',
            'pago_restante' => 'decimal:2',
            'fecha_ultimo_pago' => 'date:d/m/Y',
        ];
    }   
}