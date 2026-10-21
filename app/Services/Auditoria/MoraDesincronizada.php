<?php

namespace App\Services\Auditoria;

use Illuminate\Support\Facades\DB;

class MoraDesincronizada implements Comprobacion
{
    public function clave(): string
    {
        return 'mora';
    }

    public function nombre(): string
    {
        return 'Columnas de mora desincronizadas';
    }

    public function ejecutar(): ResultadoComprobacion
    {
        
        $resumen = DB::select("
    SELECT
    COUNT(*) AS cuotas_desincronizadas,
    COALESCE(SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)),0) AS dinero_en_juego
FROM amortizaciones am
WHERE ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01
")[0];
        $revisados=DB::table('amortizaciones')->count();
        $detalle = DB::select("
            SELECT * FROM (
                SELECT
                    c.id AS credito_id,
                    c.clave_contrato,
                    a.nombre_completo AS acreditado,
                    c.estatus AS estatus_credito,
                    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado) > 0.01) AS cuotas_desincronizadas,
                    COUNT(*) AS cuotas_del_credito,
                    SUM(am.moratorio_acumulado) AS lo_ve_portal_y_pdf,
                    SUM(am.interes_moratorio_generado) AS lo_ve_consulta_publica,
                    SUM(am.moratorio_acumulado - am.interes_moratorio_generado) AS diferencia,
                    SUM(ABS(am.moratorio_acumulado - am.interes_moratorio_generado)) AS diferencia_absoluta
                FROM amortizaciones am
                JOIN creditos c    ON c.id = am.credito_id
                JOIN acreditados a ON a.id = c.acreditado_id
                GROUP BY c.id, c.clave_contrato, a.nombre_completo, c.estatus
            ) t
            WHERE diferencia_absoluta > 0.01
            ORDER BY diferencia_absoluta DESC
        ");

        return new ResultadoComprobacion(
            clave: $this->clave(),
            nombre: $this->nombre(),
            revisados: $revisados,
            unidad: 'cuotas',
            hallazgos: (int) $resumen->cuotas_desincronizadas,
            monto: (float) $resumen->dinero_en_juego,
            detalle: $detalle,
        );
    }
}
