<?php

namespace Database\Seeders;

use App\Models\ModalidadCrea;
use Illuminate\Database\Seeder;

class ActualizarModalidadesSeeder extends Seeder
{
    /**
     * Actualiza las modalidades con datos de las convocatorias IYEM 2025.
     * Fuente: CREAEmprendedores2025.pdf, CREASustentable2025.pdf, CREAArtesanal2025.pdf
     */
    public function run(): void
    {
        $modalidades = [
            [
                'nombre'          => 'Artesanal',
                'descripcion'     => 'Dirigido a artesanos yucatecos registrados. Apoyo para adquisición de materia prima, herramientas y equipamiento artesanal.',
                'tasa_interes'    => 0.00,
                'tasa_moratoria'  => 0.00,
                'monto_minimo'    => 5000.00,
                'monto_maximo'    => 50000.00,
                'plazo_min_meses' => 6,
                'plazo_max_meses' => 18,
                'requiere_alta_sat'     => false,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => json_encode([
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del taller/actividad artesanal',
                    'constancia_artesano'  => 'Constancia o registro como artesano (SEFOTUR/FONART)',
                ]),
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
            [
                'nombre'          => 'Sustentable',
                'descripcion'     => 'Para negocios con enfoque sustentable o de impacto ambiental positivo. Proyectos de energía renovable, reciclaje, agricultura orgánica, etc.',
                'tasa_interes'    => 5.00,
                'tasa_moratoria'  => 12.50,
                'monto_minimo'    => 10000.00,
                'monto_maximo'    => 100000.00,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 24,
                'requiere_alta_sat'     => true,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => json_encode([
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del negocio o proyecto sustentable',
                    'opinion_cumplimiento' => 'Opinión de cumplimiento fiscal SAT (positiva)',
                    'plan_negocio'         => 'Breve descripción del impacto ambiental/sustentable',
                ]),
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
            [
                'nombre'          => 'Emprendedores',
                'descripcion'     => 'Para emprendedores con negocios establecidos o nuevos proyectos productivos en Yucatán. Capital de trabajo, equipamiento o expansión.',
                'tasa_interes'    => 7.00,
                'tasa_moratoria'  => 17.50,
                'monto_minimo'    => 10000.00,
                'monto_maximo'    => 150000.00,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 24,
                'requiere_alta_sat'     => true,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => json_encode([
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del negocio o proyecto',
                    'opinion_cumplimiento' => 'Opinión de cumplimiento fiscal SAT (positiva)',
                    'constancia_situacion' => 'Constancia de situación fiscal SAT',
                ]),
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
        ];

        foreach ($modalidades as $datos) {
            ModalidadCrea::where('nombre', $datos['nombre'])->update($datos);
            $this->command->info("Modalidad '{$datos['nombre']}' actualizada.");
        }
    }
}
