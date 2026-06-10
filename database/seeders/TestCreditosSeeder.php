<?php

namespace Database\Seeders;

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\Amortizacion;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestCreditosSeeder extends Seeder
{
    public function run(): void
    {
        $cajero = User::first();
        if (!$cajero) {
            $this->command->error('No hay usuarios. Crea uno primero.');
            return;
        }

        $modalidades = ModalidadCrea::all()->keyBy('nombre');

        // Buscar modalidades de forma flexible
        $modEmp = $modalidades->first(fn($m) => str_contains($m->nombre, 'Emprendedores'));
        $modSus = $modalidades->first(fn($m) => str_contains($m->nombre, 'Sustentable'));
        $modArt = $modalidades->first(fn($m) => str_contains($m->nombre, 'Artesanal'));

        if (!$modEmp && !$modSus && !$modArt) {
            // Crear modalidades si no existen
            $modEmp = ModalidadCrea::create(['nombre' => 'CREA Emprendedores', 'descripcion' => 'Tasa 7% anual']);
            $modSus = ModalidadCrea::create(['nombre' => 'CREA Sustentable',   'descripcion' => 'Tasa 5% anual, 3 meses gracia']);
            $modArt = ModalidadCrea::create(['nombre' => 'CREA Artesanal',     'descripcion' => 'Sin interés ordinario']);
        }

        $modEmp = $modEmp ?? ModalidadCrea::first();
        $modSus = $modSus ?? $modEmp;
        $modArt = $modArt ?? $modEmp;

        $casos = [
            // 1. Emprendedores $20,000 / 12 meses — pago puntual en cuota 1
            [
                'acreditado' => [
                    'nombre_completo' => 'María González Pérez',
                    'rfc'             => 'GOPM850312HYC',
                    'curp'            => 'GOPM850312MYNPER07',
                    'municipio'       => 'Mérida',
                    'sexo'            => 'M',
                    'correo'          => 'maria.gonzalez@test.com',
                ],
                'credito' => [
                    'modalidad'        => $modEmp,
                    'clave_contrato'   => 'CREA-2024-001',
                    'monto_otorgado'   => 20000,
                    'plazo_meses'      => 12,
                    'fecha_entrega'    => now()->subMonths(3)->toDateString(),
                    'tasa_ordinario'   => 7.0,
                ],
                'pagos' => [
                    // Pago puntual cuota 1 y 2
                    ['meses_atras' => 2, 'descripcion' => 'Pago puntual cuota 1'],
                    ['meses_atras' => 1, 'descripcion' => 'Pago puntual cuota 2'],
                ],
            ],

            // 2. Emprendedores $35,000 / 18 meses — con mora (paga tarde)
            [
                'acreditado' => [
                    'nombre_completo' => 'Juan Hernández Ávila',
                    'rfc'             => 'HEAJ790615HYC',
                    'curp'            => 'HEAJ790615HYNRVA08',
                    'municipio'       => 'Valladolid',
                    'sexo'            => 'H',
                    'correo'          => 'juan.hernandez@test.com',
                ],
                'credito' => [
                    'modalidad'        => $modEmp,
                    'clave_contrato'   => 'CREA-2024-002',
                    'monto_otorgado'   => 35000,
                    'plazo_meses'      => 18,
                    'fecha_entrega'    => now()->subMonths(5)->toDateString(),
                    'tasa_ordinario'   => 7.0,
                ],
                'pagos' => [
                    // Pago tardío cuota 1 (20 días después del vencimiento)
                    ['meses_atras' => 3, 'dias_tarde' => 20, 'descripcion' => 'Pago con mora cuota 1'],
                    ['meses_atras' => 2, 'dias_tarde' => 15, 'descripcion' => 'Pago con mora cuota 2'],
                ],
            ],

            // 3. Sustentable $15,000 / 6 meses — sin pagos (moroso)
            [
                'acreditado' => [
                    'nombre_completo' => 'Rosa Canche Tzec',
                    'rfc'             => 'CATR920801MYN',
                    'curp'            => 'CATR920801MYNTCM09',
                    'municipio'       => 'Tizimín',
                    'sexo'            => 'M',
                    'correo'          => 'rosa.canche@test.com',
                ],
                'credito' => [
                    'modalidad'        => $modSus ?? $modEmp,
                    'clave_contrato'   => 'CREA-2024-003',
                    'monto_otorgado'   => 15000,
                    'plazo_meses'      => 6,
                    'fecha_entrega'    => now()->subMonths(4)->toDateString(),
                    'tasa_ordinario'   => 5.0,
                ],
                'pagos' => [], // Sin pagos — moroso
            ],

            // 4. Artesanal $10,000 / 10 meses — liquidación total anticipada
            [
                'acreditado' => [
                    'nombre_completo' => 'Pedro Dzib Cauich',
                    'rfc'             => 'DZCP881120HYN',
                    'curp'            => 'DZCP881120HYNZBD6A',
                    'municipio'       => 'Izamal',
                    'sexo'            => 'H',
                    'correo'          => 'pedro.dzib@test.com',
                ],
                'credito' => [
                    'modalidad'        => $modArt ?? $modEmp,
                    'clave_contrato'   => 'CREA-2024-004',
                    'monto_otorgado'   => 10000,
                    'plazo_meses'      => 10,
                    'fecha_entrega'    => now()->subMonths(3)->toDateString(),
                    'tasa_ordinario'   => 0.0,
                ],
                'pagos' => [
                    ['meses_atras' => 2, 'descripcion' => 'Pago puntual cuota 1'],
                    ['meses_atras' => 1, 'descripcion' => 'Pago puntual cuota 2'],
                    ['liquidacion' => true, 'descripcion' => 'LIQUIDACIÓN TOTAL'],
                ],
            ],

            // 5. Emprendedores $50,000 / 24 meses — abono a capital (Reducir Plazo)
            [
                'acreditado' => [
                    'nombre_completo' => 'Lucía Tun Poot',
                    'rfc'             => 'TUPL950330MYN',
                    'curp'            => 'TUPL950330MYNTNC04',
                    'municipio'       => 'Oxkutzcab',
                    'sexo'            => 'M',
                    'correo'          => 'lucia.tun@test.com',
                ],
                'credito' => [
                    'modalidad'        => $modEmp,
                    'clave_contrato'   => 'CREA-2024-005',
                    'monto_otorgado'   => 50000,
                    'plazo_meses'      => 24,
                    'fecha_entrega'    => now()->subMonths(6)->toDateString(),
                    'tasa_ordinario'   => 7.0,
                ],
                'pagos' => [
                    ['meses_atras' => 5, 'descripcion' => 'Cuota 1 puntual'],
                    ['meses_atras' => 4, 'descripcion' => 'Cuota 2 puntual'],
                    ['meses_atras' => 3, 'descripcion' => 'Cuota 3 puntual'],
                    ['meses_atras' => 2, 'descripcion' => 'Cuota 4 puntual'],
                    // Abono extra a capital
                    ['meses_atras' => 1, 'extra_capital' => 10000, 'tipo_abono' => 'Reducir Plazo', 'descripcion' => 'Cuota 5 + abono capital'],
                ],
            ],
        ];

        foreach ($casos as $caso) {
            $this->crearCaso($cajero, $caso);
        }

        $this->command->info('✅ 5 créditos de prueba creados correctamente.');
    }

    private function crearCaso(User $cajero, array $caso): void
    {
        $mod           = $caso['credito']['modalidad'];
        $tasaOrdinario = (float) $caso['credito']['tasa_ordinario'];
        $tasaMoratoria = round($tasaOrdinario * 2.5, 2);

        // 1. Acreditado
        $acreditado = Acreditado::create($caso['acreditado']);

        // 2. Crédito
        $credito = $acreditado->creditos()->create([
            'modalidad_id'           => $mod->id,
            'clave_contrato'         => $caso['credito']['clave_contrato'],
            'monto_otorgado'         => $caso['credito']['monto_otorgado'],
            'plazo_meses'            => $caso['credito']['plazo_meses'],
            'fecha_entrega'          => $caso['credito']['fecha_entrega'],
            'tasa_interes_ordinario' => $tasaOrdinario,
            'tasa_interes_moratorio' => $tasaMoratoria,
            'estatus'                => 'Activo',
        ]);

        // 3. Tabla de amortización
        $monto        = (float) $caso['credito']['monto_otorgado'];
        $plazo        = (int) $caso['credito']['plazo_meses'];
        $tasaMensual  = ($tasaOrdinario / 100) / 12;
        $fechaInicial = Carbon::parse($caso['credito']['fecha_entrega']);
        $mesesProrroga = str_contains($mod->nombre, 'Sustentable') ? 3 : 0;

        $cuotaFija = ($tasaMensual > 0)
            ? $monto * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$plazo)))
            : $monto / $plazo;

        $saldoInsoluto = $monto;

        for ($i = 1; $i <= $plazo; $i++) {
            $interesMes = round($saldoInsoluto * $tasaMensual, 2);
            $capitalMes = ($i === $plazo)
                ? round($saldoInsoluto, 2)
                : round($cuotaFija - $interesMes, 2);
            $cuotaTotal = round($capitalMes + $interesMes, 2);

            $credito->amortizaciones()->create([
                'numero_cuota'               => $i,
                'fecha_vencimiento'          => $fechaInicial->copy()->addMonths($i + $mesesProrroga)->format('Y-m-d'),
                'saldo_insoluto'             => round($saldoInsoluto, 2),
                'capital_esperado'           => $capitalMes,
                'interes_ordinario_esperado' => $interesMes,
                'cuota_fija'                 => $cuotaTotal,
                'pago_restante'              => $cuotaTotal,
                'estado'                     => 'Pendiente',
                'capital_pagado'             => 0,
                'interes_ordinario_pagado'   => 0,
                'interes_moratorio_pagado'   => 0,
                'interes_moratorio_generado' => 0,
            ]);

            $saldoInsoluto -= $capitalMes;
        }

        // 4. Aplicar pagos de prueba
        foreach ($caso['pagos'] as $pagoConfig) {
            $this->aplicarPago($cajero, $credito, $pagoConfig);
            $credito->refresh();
        }

        $this->command->info("  → {$acreditado->nombre_completo} | {$credito->clave_contrato} | Estatus: {$credito->estatus}");
    }

    private function aplicarPago(User $cajero, Credito $credito, array $cfg): void
    {
        $tasaDiaria = ($credito->tasa_interes_moratorio / 100) / 360;

        // Determinar fecha del pago
        if (!empty($cfg['liquidacion'])) {
            $fechaPago = Carbon::now()->startOfDay();
        } elseif (isset($cfg['meses_atras'])) {
            $fechaPago = Carbon::now()->subMonths($cfg['meses_atras'])->startOfDay();
            if (!empty($cfg['dias_tarde'])) {
                $fechaPago->addDays($cfg['dias_tarde']);
            }
        } else {
            $fechaPago = Carbon::now()->startOfDay();
        }

        $cuotas = $credito->amortizaciones()
            ->where('estado', '!=', 'Pagado')
            ->orderBy('numero_cuota')
            ->get();

        if ($cuotas->isEmpty()) return;

        // Calcular cuánto se necesita pagar
        $capitalPendienteTotal = $cuotas->sum(fn($c) => (float)$c->capital_esperado - (float)$c->capital_pagado);
        $esLiquidacion = !empty($cfg['liquidacion']);

        if ($esLiquidacion) {
            // Pagar todo el capital pendiente (condonando intereses futuros)
            $montoBase = $capitalPendienteTotal;
        } else {
            // Pagar la primera cuota pendiente + su mora si aplica
            $primera = $cuotas->first();
            $venc    = Carbon::parse($primera->fecha_vencimiento)->startOfDay();
            $mora    = 0;

            if ($fechaPago->gt($venc)) {
                $dias = (int) $venc->diffInDays($fechaPago);
                if ($dias > 5) {
                    $saldoVencido = max(0, (float)$primera->saldo_insoluto - (float)$primera->capital_pagado);
                    $mora = round($saldoVencido * $tasaDiaria * $dias, 2);
                }
            }

            $montoBase = round((float)$primera->pago_restante + $mora, 2);
        }

        // Agregar extra capital si aplica
        $montoExtra = (float)($cfg['extra_capital'] ?? 0);
        $montoTotal = $montoBase + $montoExtra;

        if ($montoTotal < 0.01) return;

        // ---- Procesar pago (misma lógica que PagoController@store) ----
        $montoRestante = $montoTotal;
        $detalles      = [];
        $totMora = $totOrd = $totCap = 0;
        $interesCondonado = 0;

        foreach ($cuotas as $fila) {
            if ($montoRestante < 0.01) break;

            $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();

            // Condonar intereses futuros en liquidación total
            if ($esLiquidacion && $vencimiento->gt($fechaPago)) {
                $pendiente = round((float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado, 2);
                $interesCondonado += $pendiente;
                $fila->interes_ordinario_esperado = $fila->interes_ordinario_pagado;
            }

            // Mora (solo sobre capital, solo si vencida >5 días)
            $mora = 0;
            if ($fechaPago->gt($vencimiento)) {
                $dias = (int) $vencimiento->diffInDays($fechaPago);
                if ($dias > 5) {
                    $saldoVencido = round(max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado), 2);
                    $mora = round($saldoVencido * $tasaDiaria * $dias, 2);
                }
            }

            // Cascada: Mora → Ordinario → Capital
            $pMora = min($montoRestante, $mora);         $montoRestante -= $pMora;
            $ordP  = round((float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado, 2);
            $pOrd  = min($montoRestante, max(0, $ordP)); $montoRestante -= $pOrd;
            $capP  = round((float)$fila->capital_esperado - (float)$fila->capital_pagado, 2);
            $pCap  = min($montoRestante, max(0, $capP)); $montoRestante -= $pCap;

            $nuevoCapPag = round((float)$fila->capital_pagado + $pCap, 2);
            $nuevoOrdPag = round((float)$fila->interes_ordinario_pagado + $pOrd, 2);

            $liquidada = ($nuevoCapPag >= ((float)$fila->capital_esperado - 0.01)
                      && $nuevoOrdPag >= ((float)$fila->interes_ordinario_esperado - 0.01));

            $fila->update([
                'capital_pagado'             => $nuevoCapPag,
                'interes_ordinario_pagado'   => $nuevoOrdPag,
                'interes_moratorio_pagado'   => round((float)$fila->interes_moratorio_pagado + $pMora, 2),
                'interes_moratorio_generado' => $mora,
                'saldo_insoluto'             => round(max(0, (float)$fila->saldo_insoluto - $pCap), 2),
                'pago_restante'              => max(0, round(
                    ((float)$fila->capital_esperado + (float)$fila->interes_ordinario_esperado)
                    - ($nuevoCapPag + $nuevoOrdPag), 2
                )),
                'estado'            => $liquidada ? 'Pagado' : (($nuevoCapPag > 0 || $nuevoOrdPag > 0) ? 'Parcial' : 'Pendiente'),
                'fecha_ultimo_pago' => $fechaPago,
            ]);

            $totMora += $pMora;
            $totOrd  += $pOrd;
            $totCap  += $pCap;

            if ($pMora > 0 || $pOrd > 0 || $pCap > 0) {
                $detalles[] = ['cuota' => $fila->numero_cuota, 'cap' => $pCap, 'int' => $pOrd, 'mor' => $pMora];
            }
        }

        // Abono a capital extra (Reducir Plazo)
        if ($montoRestante > 0.01 && !$esLiquidacion && !empty($cfg['extra_capital'])) {
            $pendientes = $credito->amortizaciones()->where('estado', '!=', 'Pagado')->orderBy('numero_cuota')->get()->reverse();
            $resto = $montoRestante;
            foreach ($pendientes as $cuota) {
                if ($resto < 0.01) break;
                $capCuota = round((float)$cuota->capital_esperado - (float)$cuota->capital_pagado, 2);
                if ($resto >= $capCuota) {
                    $resto -= $capCuota;
                    $cuota->update([
                        'capital_pagado'           => $cuota->capital_esperado,
                        'interes_ordinario_pagado' => $cuota->interes_ordinario_esperado,
                        'pago_restante'            => 0,
                        'estado'                   => 'Pagado',
                        'fecha_ultimo_pago'        => $fechaPago,
                    ]);
                } else {
                    $nuevoCapPag = round((float)$cuota->capital_pagado + $resto, 2);
                    $cuota->update([
                        'capital_pagado' => $nuevoCapPag,
                        'saldo_insoluto' => round(max(0, (float)$cuota->saldo_insoluto - $resto), 2),
                        'pago_restante'  => max(0, round(
                            ((float)$cuota->capital_esperado + (float)$cuota->interes_ordinario_esperado)
                            - ($nuevoCapPag + (float)$cuota->interes_ordinario_pagado), 2
                        )),
                        'estado' => 'Parcial',
                    ]);
                    $resto = 0;
                }
            }
            $totCap += $montoRestante - $resto;
        }

        $nota = $interesCondonado > 0
            ? "\n*** CONDONACIÓN POR ANTICIPO: $" . number_format($interesCondonado, 2)
            : '';

        Pago::create([
            'credito_id'         => $credito->id,
            'acreditado_id'      => $credito->acreditado_id,
            'folio'              => Pago::generarFolio(),
            'monto_recibido'     => $montoTotal,
            'aplicado_mora'      => round($totMora, 2),
            'aplicado_ordinario' => round($totOrd, 2),
            'aplicado_capital'   => round($totCap, 2),
            'forma_pago'         => 'Efectivo',
            'fecha_pago'         => $fechaPago->toDateString(),
            'observaciones'      => ($cfg['descripcion'] ?? 'Pago de prueba') . $nota,
            'cuotas_cubiertas'   => $detalles,
            'registrado_por'     => $cajero->id,
        ]);

        // Actualizar estatus crédito
        $credito->refresh();
        $pendientes = $credito->amortizaciones()->where('estado', '!=', 'Pagado')->count();
        $nuevoEstatus = 'Activo';
        if ($pendientes === 0) {
            $nuevoEstatus = 'Liquidado';
        } elseif ($credito->amortizaciones()->where('estado', '!=', 'Pagado')->where('fecha_vencimiento', '<', now())->exists()) {
            $nuevoEstatus = 'Moroso';
        }
        $credito->update(['estatus' => $nuevoEstatus]);
    }
}
