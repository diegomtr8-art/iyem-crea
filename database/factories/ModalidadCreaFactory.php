<?php

namespace Database\Factories;

use App\Models\ModalidadCrea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModalidadCrea>
 */
class ModalidadCreaFactory extends Factory
{
    protected $model = ModalidadCrea::class;

    public function definition(): array
    {
        return [
            'nombre'         => 'Emprendedores-' . fake()->unique()->numerify('####'),
            'tasa_interes'   => 7.00,
            'tasa_moratoria' => 10.00,
        ];
    }
}
