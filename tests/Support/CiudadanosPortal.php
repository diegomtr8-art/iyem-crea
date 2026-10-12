<?php

namespace Tests\Support;

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * Los seis tipos de ciudadano del portal, armados desde cero para las pruebas
 * (no dependen de TestDataSeeder ni de los datos de la base manual).
 *
 * Cada método devuelve el User ya verificado; el crédito, si existe, se
 * alcanza con $user->solicitudCredito->credito.
 */
final class CiudadanosPortal
{
    /** Claves de los tipos, útiles para datasets de Pest. */
    public const TIPOS = [
        'sin solicitud',
        'en revisión',
        'aprobada sin crédito',
        'crédito al corriente',
        'crédito en mora',
        'crédito liquidado',
    ];

    public static function crear(string $tipo): User
    {
        return match ($tipo) {
            'sin solicitud'        => self::sinSolicitud(),
            'en revisión'          => self::enRevision(),
            'aprobada sin crédito' => self::aprobadaSinCredito(),
            'crédito al corriente' => self::alCorriente(),
            'crédito en mora'      => self::enMora(),
            'crédito liquidado'    => self::liquidado(),
        };
    }

    public static function sinSolicitud(): User
    {
        return User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);
    }

    public static function enRevision(): User
    {
        return self::conSolicitud('En_Revision');
    }

    public static function aprobadaSinCredito(): User
    {
        return self::conSolicitud('Aprobada');
    }

    /** Cuota 1 pagada, cuota 2 vence dentro de 15 días, cuota 3 en 45. */
    public static function alCorriente(): User
    {
        return self::conCredito('Activo', [
            ['numero' => 1, 'vence' => Carbon::today()->subDays(15), 'estado' => 'Pagado'],
            ['numero' => 2, 'vence' => Carbon::today()->addDays(15), 'estado' => 'Pendiente'],
            ['numero' => 3, 'vence' => Carbon::today()->addDays(45), 'estado' => 'Pendiente'],
        ]);
    }

    /**
     * Cuota 1 vencida hace $diasAtraso días (por omisión 20, fuera de la gracia
     * de 5 días). Por omisión el crédito está en estatus Moroso; ver
     * $estatus para el caso de un atraso que aún figura como Activo.
     */
    public static function enMora(int $diasAtraso = 20, string $estatus = 'Moroso'): User
    {
        return self::conCredito($estatus, [
            ['numero' => 1, 'vence' => Carbon::today()->subDays($diasAtraso), 'estado' => 'Pendiente'],
            ['numero' => 2, 'vence' => Carbon::today()->addDays(10), 'estado' => 'Pendiente'],
            ['numero' => 3, 'vence' => Carbon::today()->addDays(40), 'estado' => 'Pendiente'],
        ]);
    }

    /** Todas las cuotas pagadas y crédito Liquidado. */
    public static function liquidado(): User
    {
        return self::conCredito('Liquidado', [
            ['numero' => 1, 'vence' => Carbon::today()->subDays(75), 'estado' => 'Pagado'],
            ['numero' => 2, 'vence' => Carbon::today()->subDays(45), 'estado' => 'Pagado'],
            ['numero' => 3, 'vence' => Carbon::today()->subDays(15), 'estado' => 'Pagado'],
        ]);
    }

    private static function conSolicitud(string $estatus, array $extra = []): User
    {
        $user = self::sinSolicitud();

        SolicitudCredito::create(array_merge([
            'user_id'         => $user->id,
            'nombre_completo' => 'Ciudadano Prueba',
            'estatus'         => $estatus,
        ], $extra));

        return $user;
    }

    /**
     * @param  array<int, array{numero:int, vence:Carbon, estado:string}>  $cuotas
     */
    private static function conCredito(string $estatusCredito, array $cuotas): User
    {
        $modalidad = ModalidadCrea::create([
            'nombre'         => 'Emprendedores-' . uniqid(),
            'tasa_interes'   => 7.00,
            'tasa_moratoria' => 10.00,
        ]);

        $acreditado = Acreditado::create([
            'nombre_completo' => 'Ciudadano Prueba',
            'municipio'       => 'Mérida',
        ]);

        $credito = $acreditado->creditos()->create([
            'modalidad_id'           => $modalidad->id,
            'clave_contrato'         => 'PRB-' . uniqid(),
            'monto_otorgado'         => 3000,
            'plazo_meses'            => count($cuotas),
            'fecha_entrega'          => Carbon::today()->subMonths(3)->toDateString(),
            'tasa_interes_ordinario' => 7,
            'tasa_interes_moratorio' => 10,
            'estatus'                => $estatusCredito,
        ]);

        foreach ($cuotas as $c) {
            $pagada = $c['estado'] === 'Pagado';

            $credito->amortizaciones()->create([
                'numero_cuota'               => $c['numero'],
                'fecha_vencimiento'          => $c['vence']->toDateString(),
                'saldo_insoluto'             => 3000 - (($c['numero'] - 1) * 1000),
                'capital_esperado'           => 1000,
                'interes_ordinario_esperado' => 50,
                'cuota_fija'                 => 1050,
                'capital_pagado'             => $pagada ? 1000 : 0,
                'interes_ordinario_pagado'   => $pagada ? 50 : 0,
                'pago_restante'              => $pagada ? 0 : 1050,
                'estado'                     => $c['estado'],
            ]);
        }

        return self::conSolicitud('Aprobada', [
            'acreditado_id' => $acreditado->id,
            'credito_id'    => $credito->id,
        ]);
    }
}
