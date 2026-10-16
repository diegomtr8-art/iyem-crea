<?php

namespace Database\Factories;

use App\Models\Amortizacion;
use App\Models\Credito;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cuota de 1,000 de capital + 50 de interés, pendiente por omisión.
 *
 * @extends Factory<Amortizacion>
 */
class AmortizacionFactory extends Factory
{
    protected $model = Amortizacion::class;

    public function definition(): array
    {
        return [
            'credito_id'                 => Credito::factory(),
            'numero_cuota'               => 1,
            'fecha_vencimiento'          => Carbon::today()->addDays(15)->toDateString(),
            'saldo_insoluto'             => 3000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 50,
            'cuota_fija'                 => 1050,
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'pago_restante'              => 1050,
            'estado'                     => 'Pendiente',
        ];
    }

    public function pagada(): static
    {
        return $this->state([
            'capital_pagado'           => 1000,
            'interes_ordinario_pagado' => 50,
            'pago_restante'            => 0,
            'estado'                   => 'Pagado',
        ]);
    }
}
