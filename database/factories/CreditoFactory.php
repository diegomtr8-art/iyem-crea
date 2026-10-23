<?php

namespace Database\Factories;

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credito>
 */
class CreditoFactory extends Factory
{
    protected $model = Credito::class;

    public function definition(): array
    {
        return [
            'acreditado_id'          => Acreditado::factory(),
            'modalidad_id'           => ModalidadCrea::factory(),
            'clave_contrato'         => 'PRB-' . fake()->unique()->numerify('######'),
            'monto_otorgado'         => 3000,
            'plazo_meses'            => 3,
            'fecha_entrega'          => Carbon::today()->subMonths(3)->toDateString(),
            'tasa_interes_ordinario' => 7,
            'tasa_interes_moratorio' => 10,
            'estatus'                => 'Activo',
        ];
    }

    public function estatus(string $estatus): static
    {
        return $this->state(['estatus' => $estatus]);
    }
}
