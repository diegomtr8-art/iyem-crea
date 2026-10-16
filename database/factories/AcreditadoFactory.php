<?php

namespace Database\Factories;

use App\Models\Acreditado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Acreditado>
 */
class AcreditadoFactory extends Factory
{
    protected $model = Acreditado::class;

    public function definition(): array
    {
        return [
            'nombre_completo' => 'Ciudadano Prueba',
            'municipio'       => 'Mérida',
        ];
    }
}
