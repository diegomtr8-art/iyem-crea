<?php

namespace Database\Factories;

use App\Models\SolicitudCredito;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * SolicitudCredito no usa HasFactory; se invoca con SolicitudCreditoFactory::new().
 *
 * @extends Factory<SolicitudCredito>
 */
class SolicitudCreditoFactory extends Factory
{
    protected $model = SolicitudCredito::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'nombre_completo' => 'Ciudadano Prueba',
            'estatus'         => 'En_Revision',
        ];
    }

    public function estatus(string $estatus): static
    {
        return $this->state(['estatus' => $estatus]);
    }
}
