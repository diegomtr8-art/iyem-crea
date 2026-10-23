<?php

namespace Database\Seeders;

use App\Models\ModalidadCrea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ActualizarModalidadesSeeder extends Seeder
{
    /**
     * Completa las modalidades con datos de las convocatorias IYEM 2025.
     * Fuente: CREAEmprendedores2025.pdf, CREASustentable2025.pdf, CREAArtesanal2025.pdf
     *
     * No pisa lo que ya se cambió: crea la modalidad que falte y completa la que siga
     * como la deja ModalidadCreaSeeder. Una modalidad ya configurada no se toca (y las
     * tasas de una que ya existe, nunca); solo se avisa en qué difiere de 2025.
     */
    public function run(): void
    {
        $modalidades = [
            [
                'nombre'          => 'Artesanal',
                'tasa_interes'    => 0.00,
                'tasa_moratoria'  => 0.00,
                'monto_minimo'    => 5000.00,
                'monto_maximo'    => 25000.00,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 24,
                'requiere_alta_sat'     => false,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => [
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del taller/actividad artesanal',
                    'constancia_artesano'  => 'Constancia o registro como artesano (SEFOTUR/FONART)',
                ],
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
            [
                'nombre'          => 'Sustentable',
                'tasa_interes'    => 5.00,
                'tasa_moratoria'  => 12.50,
                'monto_minimo'    => 50000.00,
                'monto_maximo'    => 500000.00,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 24,
                'requiere_alta_sat'     => true,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => [
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del negocio o proyecto sustentable',
                    'opinion_cumplimiento' => 'Opinión de cumplimiento fiscal SAT (positiva)',
                    'plan_negocio'         => 'Breve descripción del impacto ambiental/sustentable',
                ],
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
            [
                'nombre'          => 'Emprendedores',
                'tasa_interes'    => 7.00,
                'tasa_moratoria'  => 17.50,
                'monto_minimo'    => 25000.00,
                'monto_maximo'    => 150000.00,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 24,
                'requiere_alta_sat'     => true,
                'requiere_antiguedad'   => false,
                'documentos_requeridos' => [
                    'ine_frente'           => 'INE vigente (frente)',
                    'ine_reverso'          => 'INE vigente (reverso)',
                    'curp'                 => 'CURP (documento oficial)',
                    'comprobante_domicilio'=> 'Comprobante de domicilio (no mayor a 3 meses)',
                    'foto_negocio'         => 'Fotografía del negocio o proyecto',
                    'opinion_cumplimiento' => 'Opinión de cumplimiento fiscal SAT (positiva)',
                    'constancia_situacion' => 'Constancia de situación fiscal SAT',
                ],
                'municipios_elegibles' => null,
                'activo'           => true,
            ],
        ];

        foreach ($modalidades as $datos) {
            $existentes = ModalidadCrea::where('nombre', $datos['nombre'])->get();

            if ($existentes->isEmpty()) {
                ModalidadCrea::create($datos);
                $this->command->info("Modalidad '{$datos['nombre']}' creada con los valores de 2025.");
                continue;
            }

            foreach ($existentes as $modalidad) {
                if ($this->sinConfigurar($modalidad)) {
                    // Las tasas de una modalidad que ya existe no se cambian nunca
                    $modalidad->update(Arr::except($datos, ['nombre', 'tasa_interes', 'tasa_moratoria']));
                    $this->command->info("Modalidad '{$datos['nombre']}' completada con los montos, plazos y documentos de 2025.");
                } else {
                    $this->command->info("Modalidad '{$datos['nombre']}' ya estaba configurada: no se cambió.");
                }

                if ($diferencias = $this->diferencias($modalidad, $datos)) {
                    $this->command->warn('  Difiere de 2025 en: ' . implode('; ', $diferencias) . '. Si hay que cambiarla, se hace a mano.');
                }
            }
        }
    }

    // Así la deja ModalidadCreaSeeder: con nombre y tasas, pero sin montos, plazos ni documentos
    private function sinConfigurar(ModalidadCrea $modalidad): bool
    {
        foreach (['monto_minimo', 'monto_maximo', 'plazo_min_meses', 'plazo_max_meses', 'documentos_requeridos', 'municipios_elegibles'] as $campo) {
            if ($modalidad->{$campo} !== null) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> Campos en los que la modalidad no tiene el valor de 2025 */
    private function diferencias(ModalidadCrea $modalidad, array $datos): array
    {
        $mostrar = fn ($valor) => match (true) {
            $valor === null  => 'vacío',
            is_bool($valor)  => $valor ? 'sí' : 'no',
            is_float($valor) => number_format($valor, 2, '.', ''),
            default          => (string) $valor,
        };

        $diferencias = [];
        foreach (Arr::except($datos, 'nombre') as $campo => $valor2025) {
            $actual = $modalidad->{$campo};
            $igual  = match (true) {
                is_array($valor2025) => $actual == $valor2025,
                is_bool($valor2025)  => (bool) $actual === $valor2025,
                $valor2025 === null  => $actual === null,
                default              => $actual !== null && (float) $actual === (float) $valor2025,
            };

            if (! $igual) {
                $diferencias[] = is_array($valor2025) ? $campo : "{$campo} (hoy {$mostrar($actual)}, 2025: {$mostrar($valor2025)})";
            }
        }

        return $diferencias;
    }
}
