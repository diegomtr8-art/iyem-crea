<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModalidadCrea; // Asegúrate de que el modelo exista

class ModalidadCreaSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            [
                'nombre' => 'Artesanal',
                'tasa_interes' => 0.00,
                'tasa_moratoria' => 0.00, // Por lo general es social
            ],
            [
                'nombre' => 'Sustentable',
                'tasa_interes' => 5.00,
                'tasa_moratoria' => 12.50,
            ],
            [
                'nombre' => 'Emprendedores',
                'tasa_interes' => 7.00,
                'tasa_moratoria' => 17.50,
            ],
        ];

        // firstOrCreate: correr db:seed otra vez no duplica las modalidades
        foreach ($modalidades as $modalidad) {
            ModalidadCrea::firstOrCreate(['nombre' => $modalidad['nombre']], $modalidad);
        }
    }
}