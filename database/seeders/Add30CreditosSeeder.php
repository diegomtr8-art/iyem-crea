<?php

namespace Database\Seeders;

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Add30CreditosSeeder extends Seeder
{
    public function run(): void
    {
        $cajero = User::first();
        if (!$cajero) { $this->command->error('No hay usuarios.'); return; }

        $mods   = ModalidadCrea::all();
        $emp    = $mods->first(fn($m) => str_contains($m->nombre, 'Emprendedores')) ?? ModalidadCrea::first();
        $sus    = $mods->first(fn($m) => str_contains($m->nombre, 'Sustentable'))   ?? $emp;
        $art    = $mods->first(fn($m) => str_contains($m->nombre, 'Artesanal'))     ?? $emp;

        $existentes = DB::table('creditos')->pluck('clave_contrato')->toArray();

        // activo=true  → fecha = now()-Nm +2d  (próxima cuota vence en 2 días)
        // activo=false → fecha = now()-Nm       (cuotas vencidas)
        // pagos: ma=meses_atrás, tarde=true agrega 30d, liq=true hace liquidación total

        $casos = [
            // ── ACTIVOS Emprendedores ──────────────────────────────────────────
            ['a'=>['nombre_completo'=>'Carlos Balam Puc','rfc'=>'BAPC880615HYN','curp'=>'BAPC880615HYNLRS05','municipio'=>'Mérida','sexo'=>'H','correo'=>'c.balam@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-006','monto'=>25000,'plazo'=>12,'meses'=>4,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Héctor Canché Uc','rfc'=>'CAUH830727HYN','curp'=>'CAUH830727HYNCNC11','municipio'=>'Mérida','sexo'=>'H','correo'=>'h.canche@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-012','monto'=>60000,'plazo'=>24,'meses'=>8,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>7],['ma'=>6],['ma'=>5],['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Pablo Uc Lara','rfc'=>'ULAP890514HYN','curp'=>'ULAP890514HYNCLR19','municipio'=>'Kanasín','sexo'=>'H','correo'=>'p.uc@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-020','monto'=>28000,'plazo'=>12,'meses'=>4,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Tomás Dzul Poot','rfc'=>'DZPT900422HYN','curp'=>'DZPT900422HYNDZP23','municipio'=>'Acanceh','sexo'=>'H','correo'=>'t.dzul@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-024','monto'=>33000,'plazo'=>15,'meses'=>4,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Víctor Puc Ceh','rfc'=>'PUVC851218HYN','curp'=>'PUVC851218HYNPCC25','municipio'=>'Mérida','sexo'=>'H','correo'=>'v.puc@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-026','monto'=>55000,'plazo'=>24,'meses'=>7,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>6],['ma'=>5],['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Xavier Balam Kantún','rfc'=>'BAKX881024HYN','curp'=>'BAKX881024HYNBLK27','municipio'=>'Tizimín','sexo'=>'H','correo'=>'x.balam@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-028','monto'=>18000,'plazo'=>12,'meses'=>4,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Donaldo Medina Kú','rfc'=>'MEDK890523HYN','curp'=>'MEDK890523HYNMDK33','municipio'=>'Mérida','sexo'=>'H','correo'=>'d.medina@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-034','monto'=>38000,'plazo'=>18,'meses'=>6,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>5],['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Noé Canul Herrera','rfc'=>'CAHN761107HYN','curp'=>'CAHN761107HYNNCL17','municipio'=>'Mérida','sexo'=>'H','correo'=>'n.canul@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-018','monto'=>80000,'plazo'=>24,'meses'=>8,'activo'=>true,'tasa'=>7.0],
             'p'=>[['ma'=>7],['ma'=>6],['ma'=>5],['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            // ── ACTIVOS Sustentable ─────────────────────────────────────────────
            ['a'=>['nombre_completo'=>'Esperanza Pool Moo','rfc'=>'POEM750920MYN','curp'=>'POEM750920MYNLMO06','municipio'=>'Progreso','sexo'=>'M','correo'=>'e.pool@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-007','monto'=>18000,'plazo'=>8,'meses'=>6,'activo'=>true,'tasa'=>5.0],
             'p'=>[['ma'=>3],['ma'=>2]]],

            ['a'=>['nombre_completo'=>'Graciela Tzab Cocom','rfc'=>'TACG960814MYN','curp'=>'TACG960814MYNTCZ10','municipio'=>'Ticul','sexo'=>'M','correo'=>'g.tzab@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-011','monto'=>12000,'plazo'=>6,'meses'=>5,'activo'=>true,'tasa'=>5.0],
             'p'=>[['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Karina Dzib Xool','rfc'=>'DIXK970620MYN','curp'=>'DIXK970620MYNDXL14','municipio'=>'Mérida','sexo'=>'M','correo'=>'k.dzib@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-015','monto'=>35000,'plazo'=>18,'meses'=>8,'activo'=>true,'tasa'=>5.0],
             'p'=>[['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Sandra May Caamal','rfc'=>'MACS980307MYN','curp'=>'MACS980307MYNMYC22','municipio'=>'Izamal','sexo'=>'M','correo'=>'s.may@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-023','monto'=>16000,'plazo'=>8,'meses'=>5,'activo'=>true,'tasa'=>5.0],
             'p'=>[['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Alejandra Huchin Canche','rfc'=>'HUCA011215MYN','curp'=>'HUCA011215MYNHCC30','municipio'=>'Panabá','sexo'=>'M','correo'=>'a.huchin@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-031','monto'=>14000,'plazo'=>7,'meses'=>5,'activo'=>true,'tasa'=>5.0],
             'p'=>[['ma'=>1]]],

            // ── ACTIVOS Artesanal ───────────────────────────────────────────────
            ['a'=>['nombre_completo'=>'Armando Cauich May','rfc'=>'CAMK920310HYN','curp'=>'CAMA920310HYNCMR07','municipio'=>'Motul','sexo'=>'H','correo'=>'a.cauich@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-008','monto'=>8000,'plazo'=>8,'meses'=>3,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Irene Cob Dzul','rfc'=>'CODI950413MYN','curp'=>'CODI950413MYNBDZ12','municipio'=>'Umán','sexo'=>'M','correo'=>'i.cob@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-013','monto'=>7500,'plazo'=>6,'meses'=>3,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Manuela Oxté Yam','rfc'=>'OXYM880228MYN','curp'=>'OXYM880228MYNXTM16','municipio'=>'Hunucmá','sexo'=>'M','correo'=>'m.oxte@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-017','monto'=>5000,'plazo'=>5,'meses'=>4,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Quetzal Tec Cab','rfc'=>'TECQ930726MYN','curp'=>'TECQ930726MYNTZC20','municipio'=>'Tizimín','sexo'=>'M','correo'=>'q.tec@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-021','monto'=>9000,'plazo'=>9,'meses'=>5,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Yolanda Pech May','rfc'=>'PEMY960731MYN','curp'=>'PEMY960731MYNTHM28','municipio'=>'Progreso','sexo'=>'M','correo'=>'y.pech@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-029','monto'=>11000,'plazo'=>10,'meses'=>4,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            ['a'=>['nombre_completo'=>'Celia Canche Yam','rfc'=>'CAYC940705MYN','curp'=>'CAYC940705MYNCNY32','municipio'=>'Temozon','sexo'=>'M','correo'=>'c.canche@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-033','monto'=>8500,'plazo'=>8,'meses'=>4,'activo'=>true,'tasa'=>0.0],
             'p'=>[['ma'=>3],['ma'=>2],['ma'=>1]]],

            // ── MOROSOS ─────────────────────────────────────────────────────────
            ['a'=>['nombre_completo'=>'Diana Kú Chan','rfc'=>'KUCD940518MYN','curp'=>'KUCD940518MYNCHN08','municipio'=>'Mérida','sexo'=>'M','correo'=>'d.ku@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-009','monto'=>45000,'plazo'=>18,'meses'=>3,'activo'=>false,'tasa'=>7.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Jorge Poot Tamay','rfc'=>'POTJ840930HYN','curp'=>'POTJ840930HYNPTM13','municipio'=>'Espita','sexo'=>'H','correo'=>'j.poot@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-014','monto'=>22000,'plazo'=>12,'meses'=>3,'activo'=>false,'tasa'=>7.0],
             'p'=>[['ma'=>1,'tarde'=>true]]],  // pago tarde de cuota 1, cuota 2 sigue vencida

            ['a'=>['nombre_completo'=>'Luis Cohuo Pech','rfc'=>'COPL910115HYN','curp'=>'COPL910115HYNCHP15','municipio'=>'Conkal','sexo'=>'H','correo'=>'l.cohuo@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-016','monto'=>15000,'plazo'=>10,'meses'=>3,'activo'=>false,'tasa'=>7.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Olga Cetina May','rfc'=>'CEMO950829MYN','curp'=>'CEMO950829MYNCMT18','municipio'=>'Peto','sexo'=>'M','correo'=>'o.cetina@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-019','monto'=>20000,'plazo'=>12,'meses'=>7,'activo'=>false,'tasa'=>5.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Roberto Chan Dzul','rfc'=>'CHDR801213HYN','curp'=>'CHDR801213HYNCHD21','municipio'=>'Valladolid','sexo'=>'H','correo'=>'r.chan@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-022','monto'=>40000,'plazo'=>18,'meses'=>7,'activo'=>false,'tasa'=>7.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Wendy Couch Canul','rfc'=>'COCW930611MYN','curp'=>'COCW930611MYNCCC26','municipio'=>'Maxcanú','sexo'=>'M','correo'=>'w.couch@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-027','monto'=>25000,'plazo'=>12,'meses'=>7,'activo'=>false,'tasa'=>5.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Zacarías Ek Canche','rfc'=>'ECKZ790406HYN','curp'=>'EKZZ790406HYNKCH29','municipio'=>'Mérida','sexo'=>'H','correo'=>'z.ek@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-030','monto'=>70000,'plazo'=>24,'meses'=>4,'activo'=>false,'tasa'=>7.0],
             'p'=>[]],

            ['a'=>['nombre_completo'=>'Elena Chim Uicab','rfc'=>'CIUE920814MYN','curp'=>'CIUE920814MYNCHR34','municipio'=>'Ticul','sexo'=>'M','correo'=>'e.chim@test.com'],
             'c'=>['mod'=>$sus,'clave'=>'CREA-2024-035','monto'=>30000,'plazo'=>15,'meses'=>5,'activo'=>false,'tasa'=>5.0],
             'p'=>[]],

            // ── LIQUIDADOS ───────────────────────────────────────────────────────
            ['a'=>['nombre_completo'=>'Felipe Pech Canul','rfc'=>'PECF870225HYN','curp'=>'PECF870225HYNTCN09','municipio'=>'Tekax','sexo'=>'H','correo'=>'f.pech@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-010','monto'=>30000,'plazo'=>12,'meses'=>5,'activo'=>false,'tasa'=>7.0],
             'p'=>[['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1],['liq'=>true]]],

            ['a'=>['nombre_completo'=>'Úrsula Moo Xiu','rfc'=>'MOXU941003MYN','curp'=>'MOXU941003MYNMXR24','municipio'=>'Muna','sexo'=>'M','correo'=>'u.moo@test.com'],
             'c'=>['mod'=>$art,'clave'=>'CREA-2024-025','monto'=>6000,'plazo'=>6,'meses'=>4,'activo'=>false,'tasa'=>0.0],
             'p'=>[['ma'=>3],['ma'=>2],['liq'=>true]]],

            ['a'=>['nombre_completo'=>'Bernardo Tzuc Chi','rfc'=>'TCBT870318HYN','curp'=>'TCBT870318HYNTCZ31','municipio'=>'Valladolid','sexo'=>'H','correo'=>'b.tzuc@test.com'],
             'c'=>['mod'=>$emp,'clave'=>'CREA-2024-032','monto'=>26000,'plazo'=>12,'meses'=>6,'activo'=>false,'tasa'=>7.0],
             'p'=>[['ma'=>5],['ma'=>4],['ma'=>3],['ma'=>2],['ma'=>1],['liq'=>true]]],
        ];

        $creados = 0;
        foreach ($casos as $caso) {
            if (in_array($caso['c']['clave'], $existentes)) {
                $this->command->warn("  → Saltando {$caso['c']['clave']} (ya existe)");
                continue;
            }
            $this->crearCaso($cajero, $caso);
            $creados++;
        }

        $this->command->info("✅ {$creados} créditos adicionales creados.");
    }

    private function crearCaso($cajero, array $d): void
    {
        $mod  = $d['c']['mod'];
        $tasa = (float)$d['c']['tasa'];
        $mora = round($tasa * 2.5, 2);

        $fecha = $d['c']['activo']
            ? now()->subMonths($d['c']['meses'])->addDays(2)->toDateString()
            : now()->subMonths($d['c']['meses'])->toDateString();

        $acreditado = Acreditado::create($d['a']);
        $credito    = $acreditado->creditos()->create([
            'modalidad_id'           => $mod->id,
            'clave_contrato'         => $d['c']['clave'],
            'monto_otorgado'         => $d['c']['monto'],
            'plazo_meses'            => $d['c']['plazo'],
            'fecha_entrega'          => $fecha,
            'tasa_interes_ordinario' => $tasa,
            'tasa_interes_moratorio' => $mora,
            'estatus'                => 'Activo',
        ]);

        // Tabla de amortización
        $monto      = (float)$d['c']['monto'];
        $plazo      = (int)$d['c']['plazo'];
        $tm         = ($tasa / 100) / 12;
        $base       = Carbon::parse($fecha);
        $prorroga   = str_contains($mod->nombre, 'Sustentable') ? 3 : 0;
        $cuotaFija  = ($tm > 0) ? $monto * ($tm / (1 - pow(1 + $tm, -$plazo))) : $monto / $plazo;
        $saldo      = $monto;

        for ($i = 1; $i <= $plazo; $i++) {
            $int   = round($saldo * $tm, 2);
            $cap   = ($i === $plazo) ? round($saldo, 2) : round($cuotaFija - $int, 2);
            $total = round($cap + $int, 2);
            $credito->amortizaciones()->create([
                'numero_cuota'               => $i,
                'fecha_vencimiento'          => $base->copy()->addMonths($i + $prorroga)->format('Y-m-d'),
                'saldo_insoluto'             => round($saldo, 2),
                'capital_esperado'           => $cap,
                'interes_ordinario_esperado' => $int,
                'cuota_fija'                 => $total,
                'pago_restante'              => $total,
                'estado'                     => 'Pendiente',
                'capital_pagado'             => 0,
                'interes_ordinario_pagado'   => 0,
                'interes_moratorio_pagado'   => 0,
                'interes_moratorio_generado' => 0,
            ]);
            $saldo -= $cap;
        }

        foreach ($d['p'] as $pc) {
            $this->pagar($cajero, $credito, $pc);
            $credito->refresh();
        }

        // Final status sync (needed when pagos array is empty)
        $credito->refresh();
        $pend = $credito->amortizaciones()->whereNotIn('estado', ['Pagado', 'Condonado'])->count();
        if ($pend === 0) {
            $credito->update(['estatus' => 'Liquidado']);
        } elseif ($credito->amortizaciones()->whereNotIn('estado', ['Pagado', 'Condonado'])
            ->where('fecha_vencimiento', '<', now()->toDateString())->exists()) {
            $credito->update(['estatus' => 'Moroso']);
        } else {
            $credito->update(['estatus' => 'Activo']);
        }

        $this->command->info("  → {$acreditado->nombre_completo} | {$credito->clave_contrato} | {$credito->estatus}");
    }

    private function pagar($cajero, Credito $credito, array $cfg): void
    {
        $td    = ($credito->tasa_interes_moratorio / 100) / 360;
        $esLiq = !empty($cfg['liq']);

        $fechaPago = $esLiq
            ? Carbon::now()->startOfDay()
            : Carbon::now()->subMonths($cfg['ma'])->startOfDay()->addDays(!empty($cfg['tarde']) ? 30 : 0);

        $cuotas = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado'])
            ->orderBy('numero_cuota')->get();
        if ($cuotas->isEmpty()) return;

        $montoBase = $esLiq ? $cuotas->sum(fn($q) => (float)$q->pago_restante) : null;

        if (!$esLiq) {
            $first = $cuotas->first();
            $venc  = Carbon::parse($first->fecha_vencimiento)->startOfDay();
            $mora  = 0;
            if ($fechaPago->gt($venc)) {
                $dias = (int)$venc->diffInDays($fechaPago);
                if ($dias > 5) {
                    $sv   = max(0, (float)$first->saldo_insoluto - (float)$first->capital_pagado);
                    $mora = round($sv * $td * $dias, 2);
                }
            }
            $montoBase = round((float)$first->pago_restante + $mora, 2);
        }

        $restante = $montoBase;
        $totM = $totO = $totC = $condono = 0;
        $det  = [];

        foreach ($cuotas as $f) {
            if ($restante < 0.01) break;
            $venc = Carbon::parse($f->fecha_vencimiento)->startOfDay();

            if ($esLiq && $venc->gt($fechaPago)) {
                $pInt     = round((float)$f->interes_ordinario_esperado - (float)$f->interes_ordinario_pagado, 2);
                $condono += $pInt;
                $f->interes_ordinario_esperado = $f->interes_ordinario_pagado;
            }

            $mora = 0;
            if ($fechaPago->gt($venc)) {
                $dias = (int)$venc->diffInDays($fechaPago);
                if ($dias > 5) {
                    $sv   = max(0, (float)$f->saldo_insoluto - (float)$f->capital_pagado);
                    $mora = round($sv * $td * $dias, 2);
                }
            }

            $pM = min($restante, $mora); $restante -= $pM;
            $pO = min($restante, max(0, round((float)$f->interes_ordinario_esperado - (float)$f->interes_ordinario_pagado, 2))); $restante -= $pO;
            $pC = min($restante, max(0, round((float)$f->capital_esperado - (float)$f->capital_pagado, 2))); $restante -= $pC;

            $nC  = round((float)$f->capital_pagado + $pC, 2);
            $nO  = round((float)$f->interes_ordinario_pagado + $pO, 2);
            $liq = ($nC >= (float)$f->capital_esperado - 0.01 && $nO >= (float)$f->interes_ordinario_esperado - 0.01);

            $f->update([
                'capital_pagado'             => $nC,
                'interes_ordinario_pagado'   => $nO,
                'interes_moratorio_pagado'   => round((float)$f->interes_moratorio_pagado + $pM, 2),
                'interes_moratorio_generado' => $mora,
                'saldo_insoluto'             => max(0, round((float)$f->saldo_insoluto - $pC, 2)),
                'pago_restante'              => max(0, round(((float)$f->capital_esperado + (float)$f->interes_ordinario_esperado) - ($nC + $nO), 2)),
                'estado'                     => $liq ? 'Pagado' : (($nC > 0 || $nO > 0) ? 'Parcial' : 'Pendiente'),
                'fecha_ultimo_pago'          => $fechaPago,
            ]);

            $totM += $pM; $totO += $pO; $totC += $pC;
            if ($pM > 0 || $pO > 0 || $pC > 0) {
                $det[] = ['cuota' => $f->numero_cuota, 'cap' => $pC, 'int' => $pO, 'mor' => $pM];
            }
        }

        Pago::create([
            'credito_id'         => $credito->id,
            'acreditado_id'      => $credito->acreditado_id,
            'folio'              => Pago::generarFolio(),
            'monto_recibido'     => $montoBase,
            'aplicado_mora'      => round($totM, 2),
            'aplicado_ordinario' => round($totO, 2),
            'aplicado_capital'   => round($totC, 2),
            'forma_pago'         => 'Efectivo',
            'fecha_pago'         => $fechaPago->toDateString(),
            'observaciones'      => 'Seeder' . ($condono > 0 ? " | Condonado: $".number_format($condono,2) : ''),
            'cuotas_cubiertas'   => $det,
            'registrado_por'     => $cajero->id,
        ]);

        $credito->refresh();
        $pend = $credito->amortizaciones()->whereNotIn('estado', ['Pagado', 'Condonado'])->count();
        if ($pend === 0) {
            $credito->update(['estatus' => 'Liquidado']);
        } elseif ($credito->amortizaciones()->whereNotIn('estado', ['Pagado', 'Condonado'])
            ->where('fecha_vencimiento', '<', now()->toDateString())->exists()) {
            $credito->update(['estatus' => 'Moroso']);
        } else {
            $credito->update(['estatus' => 'Activo']);
        }
    }
}
