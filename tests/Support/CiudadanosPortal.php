<?php

namespace Tests\Support;

use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\SolicitudCreditoFactory;

/**
 * Los seis tipos de ciudadano del portal, armados con factories para las pruebas
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

        SolicitudCreditoFactory::new()
            ->estatus($estatus)
            ->create(array_merge(['user_id' => $user->id], $extra));

        return $user;
    }

    /**
     * @param  array<int, array{numero:int, vence:Carbon, estado:string}>  $cuotas
     */
    private static function conCredito(string $estatusCredito, array $cuotas): User
    {
        $credito = Credito::factory()
            ->estatus($estatusCredito)
            ->create(['plazo_meses' => count($cuotas)]);

        foreach ($cuotas as $c) {
            $cuota = Amortizacion::factory()->state([
                'credito_id'        => $credito->id,
                'numero_cuota'      => $c['numero'],
                'fecha_vencimiento' => $c['vence']->toDateString(),
                'saldo_insoluto'    => 3000 - (($c['numero'] - 1) * 1000),
            ]);

            ($c['estado'] === 'Pagado' ? $cuota->pagada() : $cuota)->create();
        }

        return self::conSolicitud('Aprobada', [
            'acreditado_id' => $credito->acreditado_id,
            'credito_id'    => $credito->id,
        ]);
    }
}
